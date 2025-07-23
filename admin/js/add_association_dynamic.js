// When the DOM is fully loaded, run the main setup
document.addEventListener("DOMContentLoaded", () => {
  // Filter posts based on selected ilot on page load
  filterPostsByIlot();

  // When the board name dropdown changes, reload the list of available boards
  const boardNameSelect = document.getElementById("board_name_select");
  if (boardNameSelect) {
    boardNameSelect.addEventListener("change", handleBoardNameChange);
  }

  // When a post is selected, load the corresponding available boards
  const postSelect = document.getElementById("selected_post");
  if (postSelect) {
    postSelect.addEventListener("change", () => {
      const step_number = postSelect.value;
      const board_name = boardNameSelect.value;
      const doc_id = documentId; // global variable

      // If nothing selected, show a message and clear the board list
      if (!step_number || !board_name) {
        boardCheckboxes.innerHTML =
          '<p class="text-muted">Veuillez sélectionner un poste et un nom de carte.</p>';
        return;
      }

      // Show loading message while fetching
      boardCheckboxes.innerHTML = "<p>Chargement...</p>";

      // Build the request URL to get filtered available boards
      const url = `get_available_boards.php?step_number=${step_number}&document_id=${doc_id}&board_name=${encodeURIComponent(
        board_name
      )}`;

      // Fetch board checkboxes and display them
      fetch(url)
        .then((response) => response.text())
        .then((html) => {
          boardCheckboxes.innerHTML = html;
          boardSearch.value = "";
          boardSearch.dispatchEvent(new Event("input")); // Trigger filtering after load
        })
        .catch((err) => {
          boardCheckboxes.innerHTML =
            '<p class="text-danger">Erreur de chargement.</p>';
        });
    });
  }

  // Filter the list of board checkboxes based on user search input
  document.getElementById("board_id_search").addEventListener("input", () => {
    const query = document
      .getElementById("board_id_search")
      .value.toLowerCase();

    const checkboxes = document.querySelectorAll(
      "#board_checkboxes .form-check"
    );

    // Hide/show checkboxes based on whether label text includes the search query
    checkboxes.forEach((checkbox) => {
      const label = checkbox.querySelector("label").innerText.toLowerCase();
      checkbox.style.display = label.includes(query) ? "block" : "none";
    });
  });

  // Select all visible board checkboxes
  document.getElementById("select_all_boards").addEventListener("click", () => {
    const visibleCheckboxes = Array.from(
      document.querySelectorAll("#board_checkboxes .form-check")
    )
      .filter((el) => el.style.display !== "none")
      .map((el) => el.querySelector('input[type="checkbox"]'));

    visibleCheckboxes.forEach((cb) => (cb.checked = true));
  });

  // Deselect all visible board checkboxes
  document
    .getElementById("deselect_all_boards")
    .addEventListener("click", () => {
      const visibleCheckboxes = Array.from(
        document.querySelectorAll("#board_checkboxes .form-check")
      )
        .filter((el) => el.style.display !== "none")
        .map((el) => el.querySelector('input[type="checkbox"]'));

      visibleCheckboxes.forEach((cb) => (cb.checked = false));
    });
});

// Global array to hold mapping objects of the form { step_number, board_ids }
const mappings = [];

/**
 * Filters the options in the 'post' dropdown list based on the selected 'ilot'
 * Also selects and triggers the first matching post option automatically
 */
function filterPostsByIlot() {
  const ilotSelect = document.getElementById("ilot_select");
  const postSelect = document.getElementById("selected_post");
  const selectedIlot = ilotSelect.value;
  let firstMatchFound = false;

  // Loop through post options and show only those matching the selected ilot
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

  // Trigger change event on the selected post to load associated boards
  if (firstMatchFound) {
    postSelect.dispatchEvent(new Event("change"));
  }
}

/**
 * Loads the list of available boards (checkboxes) based on selected board name and post
 */
function handleBoardNameChange() {
  const boardName = document.getElementById("board_name_select").value;
  const postId = document.getElementById("selected_post").value;
  const docId = document.getElementById("document_id")?.value || "";

  if (!boardName || !postId) {
    document.getElementById("board_checkboxes").innerHTML =
      '<p class="text-muted">Sélectionnez un nom de carte et un poste.</p>';
    return;
  }

  document.getElementById("board_checkboxes").innerHTML =
    "<p>Chargement des cartes...</p>";

  // Fetch updated board checkboxes
  fetch(
    `get_available_boards.php?board_name=${encodeURIComponent(
      boardName
    )}&step_number=${postId}&document_id=${docId}`
  )
    .then((response) => response.text())
    .then((html) => {
      document.getElementById("board_checkboxes").innerHTML = html;
      document.getElementById("board_id_search").value = "";
    })
    .catch(() => {
      document.getElementById("board_checkboxes").innerHTML =
        '<p class="text-danger">Erreur de chargement.</p>';
    });
}

/**
 * Adds a mapping between selected post and selected board checkboxes
 * Also updates the visual list and hidden input for submission
 */
function addMapping() {
  const postSelect = document.getElementById("selected_post");
  const step_number = postSelect.value;
  const post_label = postSelect.options[postSelect.selectedIndex]?.text || "";

  const selected = document.querySelectorAll(
    '#board_checkboxes input[type="checkbox"]:checked'
  );

  // Validation
  if (!step_number || selected.length === 0) {
    alert("Veuillez sélectionner un poste et au moins une carte.");
    return;
  }

  // Extract values and labels of selected checkboxes
  const board_ids = Array.from(selected).map((cb) => cb.value);
  const board_labels = Array.from(selected).map(
    (cb) => cb.nextElementSibling.innerText
  );

  // Add to mappings array and update hidden input
  const index = mappings.length;
  mappings.push({ step_number, board_ids });
  document.getElementById("mappingsInput").value = JSON.stringify(mappings);

  // Display the new mapping visually
  const li = document.createElement("li");
  li.setAttribute("data-index", index);
  li.innerHTML = `<strong>${post_label}</strong> ↔ ${board_labels.join(", ")} 
    <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">❌</button>`;
  document.getElementById("mappingList").appendChild(li);

  // Uncheck selected boxes
  selected.forEach((cb) => (cb.checked = false));

  // Refresh the checkbox list (to exclude already mapped boards)
  handleBoardNameChange();
}

/**
 * Removes a mapping by index, updates the mappings array and visual list
 */
function removeMapping(index) {
  mappings[index] = null; // Nullify mapping at index
  document.querySelector(`li[data-index='${index}']`)?.remove();

  // Clean mappings array and update hidden input
  document.getElementById("mappingsInput").value = JSON.stringify(
    mappings.filter((m) => m) // Filter out nulls
  );
}
