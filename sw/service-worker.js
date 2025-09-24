const CACHE_NAME = 'quiz-cache-v1';
const urlsToCache = [
    '/',
    '/index.php',
    '/quiz.php',
    '/CSS/quiz.css',
       // add if you have separate JS files
    '/sounds/correct.mp3',
    '/sounds/wrong.mp3',
    '/data/coding_sample.json',
    '/data/finance_sample.json',
    '/data/general_aptitude.json',
    '/data/reasoning.json',
    '/data/vocab_sample.json',
];

// Install SW and cache files
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// Activate SW
self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Fetch from cache if offline
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request)
            .catch(() => caches.match(event.request))
    );
});
