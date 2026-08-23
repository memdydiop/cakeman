import 'simplebar'; 
import SimpleBar from 'simplebar'; //" if you want to use it manually.
import 'simplebar/dist/simplebar.css';

// Make SimpleBar globally accessible to Alpine.js
window.SimpleBar = SimpleBar;

// You will need a ResizeObserver polyfill for browsers that don't support it! (iOS Safari, Edge, ...)
import ResizeObserver from 'resize-observer-polyfill';
window.ResizeObserver = ResizeObserver;