<?php
/**
 * SQLite connection, schema creation and first-run seed data.
 * All seed people, groups and posts are fictional.
 */
declare(strict_types=1);

const SEED_PASSWORD = 'poke2004'; // shared password for the fictional seed accounts (see README)

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    $fresh = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    create_schema($pdo);
    if ($fresh || (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        seed($pdo);
    }
    return $pdo;
}

function create_schema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE COLLATE NOCASE,
        password_hash TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'Student',
        sex TEXT NOT NULL DEFAULT '',
        residence TEXT NOT NULL DEFAULT '',
        birthday TEXT NOT NULL DEFAULT '',
        hometown TEXT NOT NULL DEFAULT '',
        highschool TEXT NOT NULL DEFAULT '',
        concentration TEXT NOT NULL DEFAULT '',
        class_year TEXT NOT NULL DEFAULT '',
        looking_for TEXT NOT NULL DEFAULT '',
        interests TEXT NOT NULL DEFAULT '',
        about TEXT NOT NULL DEFAULT '',
        picture TEXT,
        privacy TEXT NOT NULL DEFAULT 'everyone',
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS friendships (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        requester_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        addressee_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at TEXT NOT NULL,
        UNIQUE(requester_id, addressee_id)
    );
    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        recipient_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        created_at TEXT NOT NULL,
        read_at TEXT,
        deleted_by_sender INTEGER NOT NULL DEFAULT 0,
        deleted_by_recipient INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS pokes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        poker_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        pokee_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS tfb_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT NOT NULL DEFAULT '',
        created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS group_members (
        group_id INTEGER NOT NULL REFERENCES tfb_groups(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        title TEXT NOT NULL DEFAULT '',
        joined_at TEXT NOT NULL,
        PRIMARY KEY (group_id, user_id)
    );
    CREATE TABLE IF NOT EXISTS group_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_id INTEGER NOT NULL REFERENCES tfb_groups(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        topic TEXT NOT NULL DEFAULT '',
        body TEXT NOT NULL,
        created_at TEXT NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_msg_recipient ON messages(recipient_id);
    CREATE INDEX IF NOT EXISTS idx_pokes_pokee ON pokes(pokee_id);
    SQL);
}

function seed(PDO $pdo): void
{
    $hash = password_hash(SEED_PASSWORD, PASSWORD_DEFAULT);
    // Fictional people only. Any resemblance to real persons is coincidental.
    $people = [
        ['Casey Northfield', 'casey.northfield', 'Student', 'Female', 'Wexford Hall 311', 'March 3, 1985', 'Portland, OR', 'Lakeview Academy', 'Computer Science', '2006', 'Friendship, Random play', 'robotics, late-night pizza, indie rock', '2004-02-04 09:12:00'],
        ['Jordan Whitlock', 'jordan.whitlock', 'Student', 'Male', 'Ashgrove House B-22', 'July 21, 1984', 'Columbus, OH', 'Riverside High School', 'Economics', '2005', 'Friendship', 'rowing, chess, jazz', '2004-02-04 13:40:00'],
        ['Priya Raman', 'priya.raman', 'Student', 'Female', 'Wexford Hall 118', 'November 9, 1985', 'Edison, NJ', 'Maple Grove High', 'Biology', '2007', 'Friendship, Study partners', 'debate, tennis, photography', '2004-02-05 18:03:00'],
        ['Theo Albright', 'theo.albright', 'Student', 'Male', 'Linden Court 4C', 'January 30, 1984', 'Austin, TX', 'Cedar Ridge School', 'Applied Math', '2005', 'Whatever I can get', 'ultimate frisbee, guitar', '2004-02-06 22:15:00'],
        ['Morgan Ellery', 'morgan.ellery', 'Alumnus/Alumna', 'Other', 'Off campus', 'May 2, 1981', 'Burlington, VT', 'Harbor Point Prep', 'History', '2003', 'Networking', 'film, hiking, crosswords', '2004-02-08 10:00:00'],
        ['Sam Okafor', 'sam.okafor', 'Student', 'Male', 'Ashgrove House C-07', 'August 14, 1985', 'Atlanta, GA', 'Northgate High', 'Physics', '2006', 'Friendship', 'basketball, sci-fi, coffee', '2004-02-09 15:30:00'],
        ['Rachel Dunmore', 'rachel.dunmore', 'Student', 'Female', 'Linden Court 2A', 'October 27, 1984', 'Madison, WI', 'Westbrook High School', 'English', '2006', 'Dating, Friendship', 'poetry, a cappella, running', '2004-02-11 11:11:00'],
        ['Leo Castellanos', 'leo.castellanos', 'Faculty', 'Male', 'Faculty Row 9', 'June 5, 1970', 'San Diego, CA', 'Pacific Heights High', 'Computer Science', '', 'Office hours', 'compilers, sailing', '2004-02-12 08:45:00'],
    ];
    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, status, sex, residence, birthday, hometown, highschool,
        concentration, class_year, looking_for, interests, about, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $domain = ALLOWED_EMAIL_DOMAIN !== '' ? ALLOWED_EMAIL_DOMAIN : 'example.edu';
    foreach ($people as $p) {
        [$name, $local, $status, $sex, $res, $bday, $home, $hs, $conc, $year, $looking, $interests, $created] = $p;
        $ins->execute([$name, "$local@$domain", $hash, $status, $sex, $res, $bday, $home, $hs, $conc, $year, $looking, $interests,
            "Fictional seed account for the thefacebook-2004 recreation.", $created, '2004-02-20 12:00:00']);
    }

    $f = $pdo->prepare('INSERT INTO friendships (requester_id, addressee_id, status, created_at) VALUES (?,?,?,?)');
    foreach ([[1, 2], [1, 3], [1, 4], [2, 4], [2, 6], [3, 7], [4, 6], [5, 1], [6, 7], [8, 1], [3, 6]] as [$a, $b]) {
        $f->execute([$a, $b, 'accepted', '2004-02-14 12:00:00']);
    }
    $f->execute([7, 1, 'pending', '2004-02-18 16:20:00']);

    $g = $pdo->prepare('INSERT INTO tfb_groups (name, description, created_by, created_at) VALUES (?,?,?,?)');
    $groups = [
        ['The Largest Thefacebook Group Of All Time', "We are trying to become the biggest group on the whole site.\n\nTell your roommates. Tell your blockmates. Tell your TF.", 2, '2004-02-10 20:00:00'],
        ['Late Night Grill Appreciation Society', "For those who believe the best meal of the day happens after midnight.", 4, '2004-02-12 01:30:00'],
        ['Problem Set Survivors', "If you have ever started a problem set at 11pm the night before it was due, you belong here.", 3, '2004-02-13 23:59:00'],
        ['Dawn Patrol Rowing Club', "5:30am on the river. Coffee after.", 2, '2004-02-15 05:30:00'],
    ];
    foreach ($groups as $gr) $g->execute($gr);

    $m = $pdo->prepare('INSERT INTO group_members (group_id, user_id, title, joined_at) VALUES (?,?,?,?)');
    $members = [
        1 => [2 => 'co-founder', 4 => 'Prime Minister', 6 => 'minister of defense', 3 => 'Director of Internal Affairs', 7 => 'Chief of Staff', 1 => '', 5 => '', 8 => ''],
        2 => [4 => 'Head Chef', 6 => '', 1 => '', 2 => ''],
        3 => [3 => 'President', 1 => 'Treasurer', 6 => '', 7 => ''],
        4 => [2 => 'Captain', 4 => '', 5 => ''],
    ];
    foreach ($members as $gid => $list) {
        foreach ($list as $uid => $title) $m->execute([$gid, $uid, $title, '2004-02-16 10:00:00']);
    }

    $p = $pdo->prepare('INSERT INTO group_posts (group_id, user_id, topic, body, created_at) VALUES (?,?,?,?,?)');
    $p->execute([1, 4, 'Recruitment drive', "Everyone invite five friends tonight. We passed 300 members this afternoon!", '2004-02-17 16:36:00']);
    $p->execute([1, 6, '', "They're at over 400 now... we need more peopleeeee", '2004-02-18 13:20:00']);
    $p->execute([1, 3, 'Course reviews', "Started a thread in Problem Set Survivors to swap notes on which sections are worth it. Come share.", '2004-02-19 01:44:00']);
    $p->execute([2, 4, 'Menu', "Spicy chicken sandwich, extra pickles. That is all.", '2004-02-14 00:45:00']);
    $p->execute([3, 3, 'Study break', "Library basement, Thursday 10pm. Bring snacks.", '2004-02-16 21:00:00']);

    $pk = $pdo->prepare('INSERT INTO pokes (poker_id, pokee_id, created_at) VALUES (?,?,?)');
    $pk->execute([2, 1, '2004-02-19 14:00:00']);

    $msg = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, created_at) VALUES (?,?,?,?,?)');
    $msg->execute([3, 1, 'problem set 3', "Are you going to section on Thursday? I'm stuck on question 4.", '2004-02-19 22:10:00']);
}
