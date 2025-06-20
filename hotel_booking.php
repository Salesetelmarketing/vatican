<?php
// Simple Hotel Booking Management in PHP with SQLite

// Initialize SQLite database
$db = new PDO('sqlite:' . __DIR__ . '/hotel.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables if not exist
$db->exec("CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number TEXT NOT NULL,
    type TEXT NOT NULL,
    price REAL NOT NULL
);");

$db->exec("CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    guest_name TEXT NOT NULL,
    guest_email TEXT NOT NULL,
    checkin DATE NOT NULL,
    checkout DATE NOT NULL,
    FOREIGN KEY(room_id) REFERENCES rooms(id)
);");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_room' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT INTO rooms(number,type,price) VALUES (?,?,?)');
    $stmt->execute([
        $_POST['number'],
        $_POST['type'],
        $_POST['price']
    ]);
    header('Location: hotel_booking.php?msg=Room+added');
    exit;
}

if ($action === 'add_booking' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT INTO bookings(room_id,guest_name,guest_email,checkin,checkout) VALUES (?,?,?,?,?)');
    $stmt->execute([
        $_POST['room_id'],
        $_POST['guest_name'],
        $_POST['guest_email'],
        $_POST['checkin'],
        $_POST['checkout']
    ]);
    header('Location: hotel_booking.php?msg=Booking+created');
    exit;
}

$rooms = $db->query('SELECT * FROM rooms ORDER BY number')->fetchAll(PDO::FETCH_ASSOC);
$bookings = $db->query('SELECT b.*, r.number AS room_number FROM bookings b JOIN rooms r ON b.room_id=r.id ORDER BY b.checkin')->fetchAll(PDO::FETCH_ASSOC);
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hotel Booking Manager</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;margin:20px;}
        h2{background:#333;color:#fff;padding:10px;border-radius:4px;}
        form{background:#fff;padding:15px;margin-bottom:20px;border-radius:4px;box-shadow:0 0 5px rgba(0,0,0,0.1);}        
        input,select{padding:8px;margin:4px 0;width:100%;}
        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th,td{border:1px solid #ccc;padding:8px;text-align:left;}
        th{background:#eee;}
        .msg{color:green;margin-bottom:10px;}
    </style>
</head>
<body>
    <h1>Hotel Booking Management</h1>
    <?php if($msg): ?><div class="msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>

    <h2>Add Room</h2>
    <form method="post">
        <input type="hidden" name="action" value="add_room">
        <label>Room Number</label>
        <input type="text" name="number" required>
        <label>Type</label>
        <input type="text" name="type" required>
        <label>Price per Night</label>
        <input type="number" step="0.01" name="price" required>
        <button type="submit">Add Room</button>
    </form>

    <h2>Current Rooms</h2>
    <table>
        <tr><th>ID</th><th>Number</th><th>Type</th><th>Price</th></tr>
        <?php foreach($rooms as $r): ?>
            <tr>
                <td><?=htmlspecialchars($r['id'])?></td>
                <td><?=htmlspecialchars($r['number'])?></td>
                <td><?=htmlspecialchars($r['type'])?></td>
                <td>$<?=htmlspecialchars(number_format($r['price'],2))?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Add Booking</h2>
    <form method="post">
        <input type="hidden" name="action" value="add_booking">
        <label>Room</label>
        <select name="room_id" required>
            <?php foreach($rooms as $r): ?>
                <option value="<?=htmlspecialchars($r['id'])?>">Room <?=htmlspecialchars($r['number'])?> (<?=htmlspecialchars($r['type'])?>)</option>
            <?php endforeach; ?>
        </select>
        <label>Guest Name</label>
        <input type="text" name="guest_name" required>
        <label>Guest Email</label>
        <input type="email" name="guest_email" required>
        <label>Check-in Date</label>
        <input type="date" name="checkin" required>
        <label>Check-out Date</label>
        <input type="date" name="checkout" required>
        <button type="submit">Create Booking</button>
    </form>

    <h2>Upcoming Bookings</h2>
    <table>
        <tr><th>ID</th><th>Room</th><th>Guest</th><th>Email</th><th>Check-in</th><th>Check-out</th></tr>
        <?php foreach($bookings as $b): ?>
            <tr>
                <td><?=htmlspecialchars($b['id'])?></td>
                <td><?=htmlspecialchars($b['room_number'])?></td>
                <td><?=htmlspecialchars($b['guest_name'])?></td>
                <td><?=htmlspecialchars($b['guest_email'])?></td>
                <td><?=htmlspecialchars($b['checkin'])?></td>
                <td><?=htmlspecialchars($b['checkout'])?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
