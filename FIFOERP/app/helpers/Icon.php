<?php
namespace App\Helpers;

class Icon {
    public static function get(string $name, int $size = 16, string $class = ''): string {
        $attrs = sprintf(
            'width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="%s"',
            $size,
            $size,
            htmlspecialchars($class)
        );

        switch ($name) {
            case 'home':
                return "<svg {$attrs}><path d=\"m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z\"/><polyline points=\"9 22 9 12 15 12 15 22\"/></svg>";
            case 'package':
                return "<svg {$attrs}><path d=\"m7.5 4.27 9 5.15\"/><path d=\"M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z\"/><path d=\"m3.3 7 8.7 5 8.7-5\"/><path d=\"M12 22V12\"/></svg>";
            case 'file-text':
                return "<svg {$attrs}><path d=\"M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z\"/><path d=\"M14 2v4a2 2 0 0 0 2 2h4\"/><path d=\"M10 9H8\"/><path d=\"M16 13H8\"/><path d=\"M16 17H8\"/></svg>";
            case 'check-square':
                return "<svg {$attrs}><rect width=\"18\" height=\"18\" x=\"3\" y=\"3\" rx=\"2\"/><path d=\"m9 12 2 2 4-4\"/></svg>";
            case 'file-check':
                return "<svg {$attrs}><path d=\"M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z\"/><path d=\"M14 2v4a2 2 0 0 0 2 2h4\"/><path d=\"m9 15 2 2 4-4\"/></svg>";
            case 'wallet':
                return "<svg {$attrs}><path d=\"M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1\"/><path d=\"M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4\"/></svg>";
            case 'file-x':
                return "<svg {$attrs}><path d=\"M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z\"/><path d=\"M14 2v4a2 2 0 0 0 2 2h4\"/><path d=\"m14.5 12.5-5 5\"/><path d=\"m9.5 12.5 5 5\"/></svg>";
            case 'layers':
                return "<svg {$attrs}><path d=\"m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z\"/><path d=\"m22 12.5-8.58 3.91a2 2 0 0 1-1.66 0L2 12.5\"/><path d=\"m22 17.5-8.58 3.91a2 2 0 0 1-1.66 0L2 17.5\"/></svg>";
            case 'droplet':
                return "<svg {$attrs}><path d=\"M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z\"/></svg>";
            case 'trending-up':
                return "<svg {$attrs}><polyline points=\"22 7 13.5 15.5 8.5 10.5 2 17\"/><polyline points=\"16 7 22 7 22 13\"/></svg>";
            case 'repeat':
                return "<svg {$attrs}><path d=\"m17 2 4 4-4 4\"/><path d=\"M3 11v-1a4 4 0 0 1 4-4h14\"/><path d=\"m7 22-4-4 4-4\"/><path d=\"M21 13v1a4 4 0 0 1-4 4H3\"/></svg>";
            case 'briefcase':
                return "<svg {$attrs}><path d=\"M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16\"/><rect width=\"20\" height=\"14\" x=\"2\" y=\"6\" rx=\"2\"/></svg>";
            case 'sliders':
                return "<svg {$attrs}><line x1=\"4\" x2=\"4\" y1=\"21\" y2=\"14\"/><line x1=\"4\" x2=\"4\" y1=\"10\" y2=\"3\"/><line x1=\"12\" x2=\"12\" y1=\"21\" y2=\"12\"/><line x1=\"12\" x2=\"12\" y1=\"8\" y2=\"3\"/><line x1=\"20\" x2=\"20\" y1=\"21\" y2=\"16\"/><line x1=\"20\" x2=\"20\" y1=\"12\" y2=\"3\"/><line x1=\"1\" x2=\"7\" y1=\"14\" y2=\"14\"/><line x1=\"9\" x2=\"15\" y1=\"8\" y2=\"8\"/><line x1=\"17\" x2=\"23\" y1=\"16\" y2=\"16\"/></svg>";
            case 'hash':
                return "<svg {$attrs}><line x1=\"4\" x2=\"20\" y1=\"9\" y2=\"9\"/><line x1=\"4\" x2=\"20\" y1=\"15\" y2=\"15\"/><line x1=\"10\" x2=\"8\" y1=\"3\" y2=\"21\"/><line x1=\"16\" x2=\"14\" y1=\"3\" y2=\"21\"/></svg>";
            case 'tag':
                return "<svg {$attrs}><path d=\"M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z\"/><circle cx=\"7\" cy=\"7\" r=\".5\" fill=\"currentColor\"/></svg>";
            case 'zap':
                return "<svg {$attrs}><polygon points=\"13 2 3 14 12 14 11 22 21 10 12 10 13 2\"/></svg>";
            case 'search':
                return "<svg {$attrs}><circle cx=\"11\" cy=\"11\" r=\"8\"/><path d=\"m21 21-4.3-4.3\"/></svg>";
            case 'plus':
                return "<svg {$attrs}><path d=\"M5 12h14\"/><path d=\"M12 5v14\"/></svg>";
            case 'refresh':
                return "<svg {$attrs}><path d=\"M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8\"/><path d=\"M3 3v5h5\"/><path d=\"M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16\"/><path d=\"M16 16h5v5\"/></svg>";
            case 'printer':
                return "<svg {$attrs}><polyline points=\"6 9 6 2 18 2 18 9\"/><path d=\"M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2\"/><rect width=\"12\" height=\"8\" x=\"6\" y=\"14\"/></svg>";
            case 'lock':
                return "<svg {$attrs}><rect width=\"18\" height=\"11\" x=\"3\" y=\"11\" rx=\"2\" ry=\"2\"/><path d=\"M7 11V7a5 5 0 0 1 10 0v4\"/></svg>";
            case 'unlock':
                return "<svg {$attrs}><rect width=\"18\" height=\"11\" x=\"3\" y=\"11\" rx=\"2\" ry=\"2\"/><path d=\"M7 11V7a5 5 0 0 1 9.9-1\"/></svg>";
            case 'file-plus':
                return "<svg {$attrs}><path d=\"M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z\"/><path d=\"M14 2v4a2 2 0 0 0 2 2h4\"/><path d=\"M9 15h6\"/><path d=\"M12 12v6\"/></svg>";
            case 'edit':
                return "<svg {$attrs}><path d=\"M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\"/><path d=\"M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\"/></svg>";
            case 'edit-3':
                return "<svg {$attrs}><path d=\"M12 20h9\"/><path d=\"M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z\"/></svg>";
            case 'check-circle':
                return "<svg {$attrs}><path d=\"M22 11.08V12a10 10 0 1 1-5.93-9.14\"/><polyline points=\"22 4 12 14.01 9 11.01\"/></svg>";
            case 'award':
                return "<svg {$attrs}><circle cx=\"12\" cy=\"8\" r=\"7\"/><polyline points=\"8.21 13.89 7 23 12 20 17 23 15.79 13.88\"/></svg>";
            case 'clipboard':
                return "<svg {$attrs}><rect width=\"8\" height=\"4\" x=\"8\" y=\"2\" rx=\"1\" ry=\"1\"/><path d=\"M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2\"/></svg>";
            case 'send':
                return "<svg {$attrs}><line x1=\"22\" y1=\"2\" x2=\"11\" y2=\"13\"/><polygon points=\"22 2 15 22 11 13 2 9 22 2\"/></svg>";
            case 'database':
                return "<svg {$attrs}><ellipse cx=\"12\" cy=\"5\" rx=\"9\" ry=\"3\"/><path d=\"M3 5V19A9 3 0 0 0 21 19V5\"/><path d=\"M3 12A9 3 0 0 0 21 12\"/></svg>";
            case 'shield':
                return "<svg {$attrs}><path d=\"M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z\"/></svg>";
            case 'folder':
                return "<svg {$attrs}><path d=\"M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z\"/></svg>";
            case 'chevron-down':
                return "<svg {$attrs}><polyline points=\"6 9 12 15 18 9\"/></svg>";
            case 'chevron-right':
                return "<svg {$attrs}><polyline points=\"9 18 15 12 9 6\"/></svg>";
            case 'eye':
                return "<svg {$attrs}><path d=\"M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z\"/><circle cx=\"12\" cy=\"12\" r=\"3\"/></svg>";
            case 'eye-off':
                return "<svg {$attrs}><path d=\"M9.88 9.88a3 3 0 1 0 4.24 4.24\"/><path d=\"M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68\"/><path d=\"M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61\"/><line x1=\"2\" x2=\"22\" y1=\"2\" y2=\"22\"/></svg>";
            default:
                return "<svg {$attrs}><circle cx=\"12\" cy=\"12\" r=\"10\"/></svg>";
        }
    }
}

