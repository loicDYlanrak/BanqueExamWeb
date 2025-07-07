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
            const form = document.getElementById('fundForm');
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

        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }

        // Animation des cartes au chargement
        window.addEventListener('load', () => {
            const cards = document.querySelectorAll('.summary-card, .funds-list, .fund-form, .history-section');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Recherche en temps réel
        const searchInput = document.querySelector('.search-box input');
        const fundItems = document.querySelectorAll('.fund-item');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            fundItems.forEach(item => {
                const fundName = item.querySelector('.fund-name').textContent.toLowerCase();
                const fundDescription = item.querySelector('.fund-description').textContent.toLowerCase();
                
                if (fundName.includes(searchTerm) || fundDescription.includes(searchTerm)) {
                    item.style.display = 'flex';
                    item.style.animation = 'fadeInUp 0.3s ease-out';
                } else {
                    item.style.display = 'none';
                }
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