import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

if (!localStorage.getItem('theme')) {
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
}
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark');
}