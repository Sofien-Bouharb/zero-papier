document.addEventListener("DOMContentLoaded", () => {
  filterPostsByIlot();

  const boardNameSelect = document.getElementById("board_name_select");
  if (boardNameSelect) {
    boardNameSelect.addEventListener("change", handleBoardNameChange);
  }

  const postSelect = document.getElementById("selected_post");
  if (postSelect) {
    postSelect.addEventListener("change", () => {
      const step_number = postSelect.value;
      const board_name = boardNameSelect.value;
      const doc_id = documentId;

      if (!step_number || !board_name) {
        boardCheckboxes.innerHTML =
          '<p class="text-muted">Veuillez sélectionner un poste et un nom de carte.</p>';
        return;
      }

      boardCheckboxes.innerHTML = "<p>Chargement...</p>";

      const url = `get_available_boards.php?step_number=${step_number}&document_id=${doc_id}&board_name=${encodeURIComponent(
        board_name
      )}`;

      fetch(url)
        .then((response) => response.text())
        .then((html) => {
          boardCheckboxes.innerHTML = html;
          boardSearch.value = "";
          boardSearch.dispatchEvent(new Event("input"));
        })
        .catch((err) => {
          boardCheckboxes.innerHTML =
            '<p class="text-danger">Erreur de chargement.</p>';
        });
    });
  }

  document.getElementById("board_id_search").addEventListener("input", () => {
    const query = document
      .getElementById("board_id_search")
      .value.toLowerCase();
    const checkboxes = document.querySelectorAll(
      "#board_checkboxes .form-check"
    );

    checkboxes.forEach((checkbox) => {
      const label = checkbox.querySelector("label").innerText.toLowerCase();
      checkbox.style.display = label.includes(query) ? "block" : "none";
    });
  });

  document.getElementById("select_all_boards").addEventListener("click", () => {
    const visibleCheckboxes = Array.from(
      document.querySelectorAll("#board_checkboxes .form-check")
    )
      .filter((el) => el.style.display !== "none")
      .map((el) => el.querySelector('input[type="checkbox"]'));
    visibleCheckboxes.forEach((cb) => (cb.checked = true));
  });

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

// Global mappings array
const mappings = [];

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

function addMapping() {
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
    <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">❌</button>`;
  document.getElementById("mappingList").appendChild(li);

  selected.forEach((cb) => (cb.checked = false));

  // Don't reset selected post — only reload checkboxes
  handleBoardNameChange();
}

function removeMapping(index) {
  mappings[index] = null;
  document.querySelector(`li[data-index='${index}']`)?.remove();
  document.getElementById("mappingsInput").value = JSON.stringify(
    mappings.filter((m) => m)
  );
}
