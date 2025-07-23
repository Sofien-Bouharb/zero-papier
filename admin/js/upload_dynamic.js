// Execute after DOM is fully loaded
document.addEventListener("DOMContentLoaded", () => {
  const boardSelect = document.getElementById("board_name_select"); // Dropdown for selecting board name
  const boardSearchInput = document.getElementById("board_id_search"); // Input for filtering boards by index ID
  const boardContainer = document.getElementById("board_checkboxes"); // Container for board checkboxes

  let lastBoardList = []; // Stores last fetched list of boards to render

  // When the board name is changed, clear search input and load matching boards
  boardSelect.addEventListener("change", () => {
    boardSearchInput.value = "";
    loadBoards();
  });

  // Search boards using the input field value
  window.searchBoards = function () {
    if (!boardSelect.value) {
      alert("Veuillez sélectionner un nom de carte.");
      return;
    }
    loadBoards(boardSearchInput.value.trim()); // Search with current board index ID
  };

  /**
   * Load boards from server by board name and optional board index ID
   * This function sends an AJAX request and triggers rendering
   */
  function loadBoards(boardIndexId = "") {
    boardContainer.innerHTML =
      '<div class="text-center py-2">Chargement...</div>';

    fetch(
      `get_boards_by_name.php?board_name=${encodeURIComponent(
        boardSelect.value
      )}&board_index_id=${encodeURIComponent(boardIndexId)}`
    )
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          boardContainer.innerHTML = `<div class="text-danger">${data.error}</div>`;
          return;
        }

        lastBoardList = data; // Save board list for rendering
        renderBoardCheckboxes(); // Display checkboxes for each board
      })
      .catch((err) => {
        console.error("Erreur:", err);
        boardContainer.innerHTML =
          '<div class="text-danger">Erreur lors du chargement.</div>';
      });
  }

  /**
   * Display board checkboxes in the DOM using `lastBoardList`
   */
  function renderBoardCheckboxes() {
    boardContainer.innerHTML = "";

    if (lastBoardList.length === 0) {
      boardContainer.innerHTML =
        '<div class="text-muted">Aucun résultat trouvé.</div>';
      return;
    }

    lastBoardList.forEach((board) => {
      const wrapper = document.createElement("div");
      wrapper.className = "form-check";
      wrapper.innerHTML = `
        <input class="form-check-input" type="checkbox" value="${board.board_index_id}" id="board${board.board_index_id}">
        <label class="form-check-label" for="board${board.board_index_id}">
          ${board.board_name} (ID: ${board.board_index_id})
        </label>
      `;
      boardContainer.appendChild(wrapper);
    });
  }

  // Select all visible board checkboxes
  window.selectAllBoards = function () {
    boardContainer
      .querySelectorAll('input[type="checkbox"]')
      .forEach((cb) => (cb.checked = true));
  };

  // Deselect all visible board checkboxes
  window.deselectAllBoards = function () {
    boardContainer
      .querySelectorAll('input[type="checkbox"]')
      .forEach((cb) => (cb.checked = false));
  };

  /**
   * Add a mapping between a selected post and selected boards
   * Updates the mappings list and hidden input field
   */
  window.addMapping = function () {
    const postSelect = document.getElementById("selected_post");
    const postId = postSelect.value;
    const postLabel = postSelect.options[postSelect.selectedIndex].text;

    // Get checked board checkboxes
    const checkboxes = boardContainer.querySelectorAll(
      'input[type="checkbox"]:checked'
    );

    const boardIds = Array.from(checkboxes).map((cb) => cb.value);
    const boardLabels = Array.from(checkboxes).map(
      (cb) => cb.nextElementSibling.innerText
    );

    // Validate selection
    if (!postId || boardIds.length === 0) {
      alert("Veuillez sélectionner un poste et au moins une carte.");
      return;
    }

    const mappingsInput = document.getElementById("mappingsInput");
    const mappingList = document.getElementById("mappingList");

    // Initialize mappings array if not already present
    if (!window.mappings) window.mappings = [];
    const index = window.mappings.length;

    // Add mapping to array and update hidden input
    window.mappings.push({ step_number: postId, board_ids: boardIds });
    mappingsInput.value = JSON.stringify(window.mappings);

    // Create visual list item for the mapping
    const li = document.createElement("li");
    li.setAttribute("data-index", index);
    li.innerHTML = `<strong>${postLabel}</strong> ↔ ${boardLabels.join(
      ", "
    )} <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">❌</button>`;
    mappingList.appendChild(li);

    // Reset form UI
    checkboxes.forEach((cb) => (cb.checked = false));
    boardSearchInput.value = "";
    boardSelect.selectedIndex = 0;
    boardContainer.innerHTML = "";
  };

  /**
   * Remove a mapping from the list and update the hidden input
   */
  window.removeMapping = function (index) {
    if (window.mappings) {
      window.mappings[index] = null; // Mark for removal
      document.querySelector(`li[data-index='${index}']`).remove();

      // Clean the array and update hidden input
      document.getElementById("mappingsInput").value = JSON.stringify(
        window.mappings.filter((m) => m) // Remove null entries
      );
    }
  };

  /**
   * Filter the posts dropdown based on the selected ilot
   * Only posts that belong to the selected ilot will be shown
   */
  window.filterPostsByIlot = function () {
    const ilotSelect = document.getElementById("ilot_select");
    const selectedIlot = ilotSelect.value;
    const postSelect = document.getElementById("selected_post");
    let found = false;

    Array.from(postSelect.options).forEach((option) => {
      if (!option.value) {
        option.style.display = "block";
        return;
      }
      const ilotId = option.getAttribute("data-ilot-id");

      if (ilotId === selectedIlot) {
        option.style.display = "block";
        if (!found) {
          option.selected = true;
          found = true;
        }
      } else {
        option.style.display = "none";
        option.selected = false;
      }
    });

    if (!found) postSelect.selectedIndex = 0;
  };

  // Call ilot filter on page load to initialize post dropdown
  filterPostsByIlot();
});
