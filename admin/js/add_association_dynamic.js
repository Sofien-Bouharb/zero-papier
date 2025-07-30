// When the DOM is fully loaded, run the main setup
document.addEventListener("DOMContentLoaded", () => {
  const postSelect = document.getElementById("selected_post");
  const boardNameSelect = document.getElementById("board_name_select");
  const boardSearchInput = document.getElementById("board_id_search");
  const boardContainer = document.getElementById("board_checkboxes");
  const docId = document.getElementById("document_id").value;

  // Filter posts based on selected ilot on page load
  filterPostsByIlot();

  // When post or board name changes, clear search input and load boards
  postSelect.addEventListener("change", () => {
    boardSearchInput.value = "";
    loadBoards();
  });

  boardNameSelect.addEventListener("change", () => {
    boardSearchInput.value = "";
    loadBoards();
  });

  // Enter key on search input
  boardSearchInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      window.searchBoards();
    }
  });

  // Define window functions
  window.searchBoards = function () {
    const stepNumber = postSelect.value;
    const boardName = boardNameSelect.value;
    if (!stepNumber || !boardName) {
      alert("Veuillez sélectionner un poste et un nom de carte.");
      return;
    }
    loadBoards(boardSearchInput.value.trim());
  };

  window.selectAllBoards = function () {
    const checkboxes = boardContainer.querySelectorAll(
      'input[type="checkbox"]'
    );
    checkboxes.forEach((cb) => (cb.checked = true));
  };

  window.deselectAllBoards = function () {
    const checkboxes = boardContainer.querySelectorAll(
      'input[type="checkbox"]'
    );
    checkboxes.forEach((cb) => (cb.checked = false));
  };

  // Load boards function
  function loadBoards(searchQuery = "") {
    const stepNumber = postSelect.value;
    const boardName = boardNameSelect.value;
    if (!stepNumber || !boardName) {
      boardContainer.innerHTML =
        '<p class="text-muted">Veuillez sélectionner un poste et un nom de carte.</p>';
      return;
    }

    boardContainer.innerHTML =
      '<div class="text-center py-2">Chargement...</div>';

    let url = `get_available_boards.php?step_number=${encodeURIComponent(
      stepNumber
    )}&document_id=${encodeURIComponent(docId)}&board_name=${encodeURIComponent(
      boardName
    )}`;
    if (searchQuery) {
      url += `&search_query=${encodeURIComponent(searchQuery)}`;
    }

    fetch(url)
      .then((response) => response.text())
      .then((html) => {
        boardContainer.innerHTML = html;
      })
      .catch((err) => {
        console.error("Erreur:", err);
        boardContainer.innerHTML =
          '<div class="text-danger">Erreur lors du chargement.</div>';
      });
  }

  // Add mapping function
  window.addMapping = function () {
    const postSelect = document.getElementById("selected_post");
    const step_number = postSelect.value;
    const post_label = postSelect.options[postSelect.selectedIndex]?.text || "";

    const selected = document.querySelectorAll(
      '#board_checkboxes input[type="checkbox"]:checked'
    );

    if (!step_number || selected.length === 0) {
      alert("Veuillez sélectionner un poste et au moins une carte.");
      return;
    }

    const board_ids = Array.from(selected).map((cb) => cb.value);
    const board_labels = Array.from(selected).map(
      (cb) => cb.nextElementSibling.innerText
    );

    const index = mappings.length;
    mappings.push({ step_number, board_ids });
    document.getElementById("mappingsInput").value = JSON.stringify(mappings);

    const li = document.createElement("li");
    li.setAttribute("data-index", index);
    li.innerHTML = `<strong>${post_label}</strong> ↔ ${board_labels.join(", ")} 
  <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">
    <img src="../../assets/emojis/0000.png" alt="❌" class="emoji">
  </button>`;

    document.getElementById("mappingList").appendChild(li);

    // Reset form UI
    selected.forEach((cb) => (cb.checked = false));
    boardSearchInput.value = "";
    boardNameSelect.selectedIndex = 0; // Reset board name dropdown to default
    boardContainer.innerHTML =
      '<p class="text-muted">Sélectionnez un nom de carte et un poste.</p>';
  };

  // Remove mapping function
  window.removeMapping = function (index) {
    mappings[index] = null;
    document.querySelector(`li[data-index='${index}']`)?.remove();
    document.getElementById("mappingsInput").value = JSON.stringify(
      mappings.filter((m) => m)
    );
  };
});

// Global array to hold mapping objects
const mappings = [];

// Filter posts by ilot
function filterPostsByIlot() {
  const ilotSelect = document.getElementById("ilot_select");
  const postSelect = document.getElementById("selected_post");
  const selectedIlot = ilotSelect.value;
  let firstMatchFound = false;

  Array.from(postSelect.options).forEach((option) => {
    if (!option.value) return;
    const ilotId = option.getAttribute("data-ilot-id");
    if (ilotId === selectedIlot) {
      option.style.display = "block";
      if (!firstMatchFound) {
        option.selected = true;
        firstMatchFound = true;
      }
    } else {
      option.style.display = "none";
    }
  });

  if (firstMatchFound) {
    postSelect.dispatchEvent(new Event("change"));
  }
}
