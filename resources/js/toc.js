// Generate Table of Contents from page headings
document.addEventListener('DOMContentLoaded', () => {
    const tocNav = document.getElementById('toc-nav');
    if (!tocNav) return;

    const mainContent = document.getElementById('main-content');
    if (!mainContent) return;

    // Get all headings (h2 and h3)
    const headings = mainContent.querySelectorAll('h2, h3');
    
    if (headings.length === 0) {
        tocNav.parentElement.style.display = 'none';
        return;
    }

    const tocList = document.createElement('ul');
    tocList.className = 'space-y-2';

    headings.forEach((heading, index) => {
        // Skip headings without text or id
        if (!heading.textContent.trim()) return;

        // Create ID if it doesn't exist
        if (!heading.id) {
            heading.id = heading.textContent
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
        }

        const li = document.createElement('li');
        const link = document.createElement('a');
        
        link.href = `#${heading.id}`;
        link.textContent = heading.textContent.replace(/^[📋🗄️🔧🔗🔍🎯⚡🎪💡🌐🔗📚]+\s*/, ''); // Remove emoji
        link.className = heading.tagName === 'H2' 
            ? 'block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-medium'
            : 'block text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors pl-4 text-xs';

        // Highlight active section on scroll
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(heading.id);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.history.pushState(null, '', `#${heading.id}`);
            }
        });

        li.appendChild(link);
        tocList.appendChild(li);
    });

    tocNav.appendChild(tocList);

    // Highlight current section on scroll
    let activeLink = null;
    
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    const links = tocNav.querySelectorAll('a');
                    
                    links.forEach((link) => {
                        if (link.getAttribute('href') === `#${id}`) {
                            if (activeLink) {
                                activeLink.classList.remove('text-blue-600', 'dark:text-blue-400', 'font-semibold');
                                activeLink.classList.add('text-gray-700', 'dark:text-gray-300');
                            }
                            link.classList.add('text-blue-600', 'dark:text-blue-400', 'font-semibold');
                            link.classList.remove('text-gray-700', 'dark:text-gray-300');
                            activeLink = link;
                        }
                    });
                }
            });
        },
        {
            rootMargin: '-100px 0px -80% 0px',
            threshold: 0
        }
    );

    headings.forEach((heading) => {
        observer.observe(heading);
    });
});
