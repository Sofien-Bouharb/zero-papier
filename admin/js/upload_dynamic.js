// upload_dynamic.js

document.addEventListener("DOMContentLoaded", () => {
  const boardSelect = document.getElementById("board_name_select");
  const boardSearchInput = document.getElementById("board_id_search");
  const boardContainer = document.getElementById("board_checkboxes");

  let lastBoardList = [];

  boardSelect.addEventListener("change", () => {
    boardSearchInput.value = "";
    loadBoards();
  });

  window.searchBoards = function () {
    if (!boardSelect.value) {
      alert("Veuillez sélectionner un nom de carte.");
      return;
    }
    loadBoards(boardSearchInput.value.trim());
  };

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

        lastBoardList = data;
        renderBoardCheckboxes();
      })
      .catch((err) => {
        console.error("Erreur:", err);
        boardContainer.innerHTML =
          '<div class="text-danger">Erreur lors du chargement.</div>';
      });
  }

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

  window.selectAllBoards = function () {
    boardContainer
      .querySelectorAll('input[type="checkbox"]')
      .forEach((cb) => (cb.checked = true));
  };

  window.deselectAllBoards = function () {
    boardContainer
      .querySelectorAll('input[type="checkbox"]')
      .forEach((cb) => (cb.checked = false));
  };

  window.addMapping = function () {
    const postSelect = document.getElementById("selected_post");
    const postId = postSelect.value;
    const postLabel = postSelect.options[postSelect.selectedIndex].text;
    const checkboxes = boardContainer.querySelectorAll(
      'input[type="checkbox"]:checked'
    );

    const boardIds = Array.from(checkboxes).map((cb) => cb.value);
    const boardLabels = Array.from(checkboxes).map(
      (cb) => cb.nextElementSibling.innerText
    );

    if (!postId || boardIds.length === 0) {
      alert("Veuillez sélectionner un poste et au moins une carte.");
      return;
    }

    const mappingsInput = document.getElementById("mappingsInput");
    const mappingList = document.getElementById("mappingList");

    if (!window.mappings) window.mappings = [];
    const index = window.mappings.length;

    window.mappings.push({ step_number: postId, board_ids: boardIds });
    mappingsInput.value = JSON.stringify(window.mappings);

    const li = document.createElement("li");
    li.setAttribute("data-index", index);
    li.innerHTML = `<strong>${postLabel}</strong> ↔ ${boardLabels.join(
      ", "
    )} <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">❌</button>`;
    mappingList.appendChild(li);

    checkboxes.forEach((cb) => (cb.checked = false));
    boardSearchInput.value = "";
    boardSelect.selectedIndex = 0;
    boardContainer.innerHTML = "";
  };

  window.removeMapping = function (index) {
    if (window.mappings) {
      window.mappings[index] = null;
      document.querySelector(`li[data-index='${index}']`).remove();
      document.getElementById("mappingsInput").value = JSON.stringify(
        window.mappings.filter((m) => m)
      );
    }
  };

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

  filterPostsByIlot();
});
