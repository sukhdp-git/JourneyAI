<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Trading\Wall;

/** Public Wall of L's: approved, anonymised loss autopsies with emoji reactions. */
final class WallController extends Controller
{
    public function index(Request $req): never
    {
        $this->render('wall', ['posts' => Wall::approved()], [
            'title' => 'Wall of L\'s — real traders, real losses, real lessons', 'path' => '/wall',
            'description' => 'Every journal posts green screenshots. We post the losses: anonymous trade autopsies from real traders, and the lesson behind each one.',
        ]);
    }

    public function react(Request $req): never
    {
        Csrf::verify($req);
        $id = (int) ($req->params['id'] ?? 0);
        $kind = (string) $req->post('r', '');
        if (!isset(Wall::REACTIONS[$kind]) || !Database::value("SELECT id FROM wall_posts WHERE id = :id AND status = 'approved'", ['id' => $id])) {
            Response::json(['ok' => false], 404);
        }
        if (!RateLimiter::hit('wall:' . $req->ip() . ':' . $id . ':' . $kind, 1, 30 * 86400) || !RateLimiter::hit('wall:' . $req->ip(), 60, 3600)) {
            Response::json(['ok' => false, 'error' => 'Already reacted.'], 429);
        }
        Database::query("UPDATE wall_posts SET react_$kind = react_$kind + 1 WHERE id = :id", ['id' => $id]);
        Response::json(['ok' => true, 'count' => (int) Database::value("SELECT react_$kind FROM wall_posts WHERE id = :id", ['id' => $id])]);
    }
}
