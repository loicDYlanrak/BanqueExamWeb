let selectedPrets = [];

function toggleCompareMode() {
  const btn = document.querySelector(".clke");
  btn.classList.toggle("active");
  
  if (btn.classList.contains("active")) {
    btn.textContent = "Annuler la comparaison";
    document.querySelector(".loans-list").classList.add("compare-mode");
    selectedPrets = [];
  } else {
    btn.textContent = "Comparer 2 simulations";
    document.querySelector(".loans-list").classList.remove("compare-mode");
    document.getElementById("compare-btn").style.display = "none";
  }
  
  chargerPrets();
}

function updateCompareButton() {
  const checkboxes = document.querySelectorAll(".compare-checkbox:checked");
  selectedPrets = Array.from(checkboxes).map(cb => parseInt(cb.getAttribute("data-pret-id")));
  
  const compareBtn = document.getElementById("compare-btn") || createCompareButton();
  compareBtn.style.display = selectedPrets.length === 2 ? "inline-block" : "none";
}

function createCompareButton() {
  const btn = document.createElement("button");
  btn.id = "compare-btn";
  btn.className = "btn btn-secondary clk";
  btn.textContent = "Comparer les prêts sélectionnés";
  btn.style.display = "none";
  btn.style.marginLeft = "10px";
  btn.onclick = comparePrets;
  
  document.querySelector(".list-header").appendChild(btn);
  return btn;
}

function comparePrets() {
  if (selectedPrets.length !== 2) return;
  
  // Charger les données des deux prêts
  Promise.all([
    fetchPretDetails(selectedPrets[0]),
    fetchPretDetails(selectedPrets[1]),
    fetchEcheancier(selectedPrets[0]),
    fetchEcheancier(selectedPrets[1])
  ]).then(([pret1, pret2, echeancier1, echeancier2]) => {
    // Afficher la comparaison dans le modal
    displayComparison(pret1, pret2, echeancier1, echeancier2);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById("modalCompare"));
    modal.show();
  }).catch(error => {
    alert("Erreur lors du chargement des données de comparaison: " + error);
  });
}

function fetchPretDetails(pretId) {
  return new Promise((resolve, reject) => {
    ajax("GET", `/prets/${pretId}`, null, resolve, reject);
  });
}

function fetchEcheancier(pretId) {
  return new Promise((resolve, reject) => {
    ajax("GET", `/prets/${pretId}/annuite`, null, resolve, reject);
  });
}

function displayComparison(pret1, pret2, echeancier1, echeancier2) {
  // Afficher les échéanciers
  displayEcheancierComparison("compare-pret-1", pret1, echeancier1);
  displayEcheancierComparison("compare-pret-2", pret2, echeancier2);
  
  // Afficher le résumé de comparaison
  const summary = document.getElementById("compare-summary");
  summary.innerHTML = `
    <h5>Résumé de la comparaison</h5>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Critère</th>
          <th>Prêt 1 (${pret1.type_nom})</th>
          <th>Prêt 2 (${pret2.type_nom})</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Montant</td>
          <td>Ar ${pret1.montant}</td>
          <td>Ar ${pret2.montant}</td>
        </tr>
        <tr>
          <td>Taux d'intérêt</td>
          <td>${pret1.taux}%</td>
          <td>${pret2.taux}%</td>
        </tr>
        <tr>
          <td>Durée</td>
          <td>${pret1.duree_mois} mois</td>
          <td>${pret2.duree_mois} mois</td>
        </tr>
        <tr>
          <td>Total intérêts</td>
          <td>Ar ${echeancier1.reduce((sum, e) => sum + parseFloat(e.interet), 0)}</td>
          <td>Ar ${echeancier2.reduce((sum, e) => sum + parseFloat(e.interet), 0)}</td>
        </tr>
      </tbody>
    </table>
  `;
}

function displayEcheancierComparison(containerId, pret, echeancier) {
  const container = document.getElementById(containerId);
  container.innerHTML = `
    <h6>${pret.nom} ${pret.prenom} - ${pret.type_nom}</h6>
    <table class="table table-striped table-bordered">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Année</th>
          <th>Capital</th>
          <th>Intérêt</th>
          <th>Annuité</th>
          <th>Reste à payer</th>
        </tr>
      </thead>
      <tbody>
        ${echeancier.map(e => `
          <tr>
            <td>${e.periode}</td>
            <td>${e.annee}</td>
            <td>${e.pret}</td>
            <td>${e.interet}</td>
            <td>${e.mensualite}</td>
            <td>${e.valeur_net}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
    ${pret.est_actif ? '' : `
      <div class="text-end mt-3">
        <button class="btn btn-primary" onclick="validerPret(${pret.id})"> Valider définitivement le prêt</button>
      </div>
    `}
  `;
}

function validerPret(pretId) {
  if (!confirm("Valider ce prêt et générer les mensualités ?")) return;
  ajax("POST", `/prets/${pretId}/valider`, "", (res) => {
    alert(" Prêt validé avec succès !");
    const modal = bootstrap.Modal.getInstance(document.getElementById("modalCompare"));
    modal.hide();
    chargerPrets();
  });
}