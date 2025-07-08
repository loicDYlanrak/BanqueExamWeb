        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
        }

        function filterLoans() {
            const loanItems = document.querySelectorAll('.loan-item');
            const filters = document.querySelectorAll('.filter-select');
            
            loanItems.forEach(item => {
                let show = true;
                
                filters.forEach(filter => {
                    const filterValue = filter.value;
                    if (filterValue) {
                        const filterType = filter.previousElementSibling.textContent.toLowerCase();
                        
                        if (filterType.includes('statut')) {
                            if (item.dataset.status !== filterValue) show = false;
                        } else if (filterType.includes('type')) {
                            if (item.dataset.type !== filterValue) show = false;
                        } else if (filterType.includes('montant')) {
                            const amount = parseInt(item.dataset.amount);
                            if (filterValue === '0-10000' && amount > 10000) show = false;
                            if (filterValue === '10000-50000' && (amount <= 10000 || amount > 50000)) show = false;
                            if (filterValue === '50000+' && amount <= 50000) show = false;
                        }
                    }
                });
                
                item.style.display = show ? 'flex' : 'none';
                if (show) {
                    item.style.animation = 'fadeInUp 0.3s ease-out';
                }
            });
        }

        function searchLoans(searchTerm) {
            const loanItems = document.querySelectorAll('.loan-item');
            const term = searchTerm.toLowerCase();
            
            loanItems.forEach(item => {
                const clientName = item.querySelector('.loan-client').textContent.toLowerCase();
                const loanType = item.querySelector('.loan-type').textContent.toLowerCase();
                
                if (clientName.includes(term) || loanType.includes(term)) {
                    item.style.display = 'flex';
                    item.style.animation = 'fadeInUp 0.3s ease-out';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function openNewLoanModal() {
            alert('Fonction de nouveau prêt - À implémenter');
        }

        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }

        // Animation des éléments au chargement
        window.addEventListener('load', () => {
            const elements = document.querySelectorAll('.filters-section, .loans-list, .quick-stats, .loan-types');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Parallax effect
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                const speed = 0.5 + (index * 0.1);
                shape.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // Gestion des actions sur les prêts
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('action-btn')) {
                const action = e.target.textContent.trim();
                const loanClient = e.target.closest('.loan-item').querySelector('.loan-client').textContent;
                
                if (action === 'Payer') {
                    alert(`Enregistrer un paiement pour ${loanClient}`);
                } else if (action === 'Détails') {
                    alert(`Afficher les détails du prêt de ${loanClient}`);
                } else if (action === 'Évaluer') {
                    alert(`Évaluer la demande de prêt de ${loanClient}`);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
    // Ensure Bootstrap modal functionality is initialized
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    });

    // Center modal vertically when shown
    const modalDialogs = document.querySelectorAll('.modal-dialog');
    modalDialogs.forEach(dialog => {
        const modal = dialog.closest('.modal');
        modal.addEventListener('show.bs.modal', function () {
        });
    });

    // Adjust modal on window resize
    window.addEventListener('resize', function () {
        modalDialogs.forEach(dialog => {
            if (dialog.closest('.modal.show')) {
                
            }
        });
    });
});