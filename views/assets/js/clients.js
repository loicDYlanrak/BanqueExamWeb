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

        function toggleForm() {
            const form = document.getElementById('clientForm');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                form.style.animation = 'slideInRight 0.5s ease-out';
            } else {
                form.style.animation = 'slideOutRight 0.5s ease-out';
                setTimeout(() => {
                    form.style.display = 'none';
                }, 500);
            }
        }

        function searchClients(searchTerm = '') {
            const clientItems = document.querySelectorAll('.client-item');
            const term = searchTerm.toLowerCase();
            
            clientItems.forEach(item => {
                const name = item.dataset.name;
                const phone = item.dataset.phone;
                
                if (name.includes(term) || phone.includes(term)) {
                    item.style.display = 'flex';
                    item.style.animation = 'fadeInUp 0.3s ease-out';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }

        // Animation des éléments au chargement
        window.addEventListener('load', () => {
            const elements = document.querySelectorAll('.search-filters, .clients-list, .client-stats-sidebar, .client-form');
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

        // Gestion du clic sur les clients
        document.addEventListener('click', (e) => {
            if (e.target.closest('.client-item')) {
                const clientName = e.target.closest('.client-item').querySelector('.client-name').textContent;
                alert(`Afficher la fiche détaillée de ${clientName}`);
            }
        });

        // Gestion du formulaire
        document.querySelector('.client-form form').addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Client ajouté avec succès !');
            e.target.reset();
        });