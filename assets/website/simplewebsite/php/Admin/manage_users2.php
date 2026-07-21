<?php
session_start();
require_once 'db.php';

// Set content-type header to prevent charset issues during AJAX
header('Content-Type: text/html; charset=UTF-8');

$search = $_GET['search'] ?? '';
$searchTerm = '%' . $search . '%';

$query = "
  SELECT A.UserNum, A.ID, A.CreateDate, A.LoginTime, A.LastIP, A.AuthType,
         CASE 
            WHEN EXISTS (
              SELECT 1 
              FROM cabal_game_block_ip C 
              WHERE C.ip = A.LastIP AND C.type = 2
            ) THEN 1 ELSE 0 
         END AS IsIPBlocked
  FROM cabal_auth_table A
";

if (!empty($search)) {
    $query .= " WHERE A.ID LIKE :search";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':search', $searchTerm);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="p-6 bg-gray-900 rounded-lg shadow text-white">
    <form method="GET" class="mb-6 flex items-center space-x-4">
        <input type="text" name="search" placeholder="Search by username..." value="<?= htmlspecialchars($search); ?>"
               class="px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-400">
        <button type="submit" class="bg-yellow-400 text-black px-4 py-2 rounded-lg hover:bg-yellow-500 transition">Search</button>
    </form>

    <h2 class="text-2xl font-bold mb-4">User List</h2>
    <div class="overflow-x-auto">
        <table class="w-full bg-gray-800 rounded-lg overflow-hidden text-sm">
            <thead class="bg-gray-700 text-yellow-300">
                <tr>
                    <th class="p-2 text-left">UserNum</th>
                    <th class="p-2 text-left">Username</th>
                    <th class="p-2 text-left">Join Date</th>
                    <th class="p-2 text-left">Last Login</th>
                    <th class="p-2 text-left">Last IP</th>
                    <th class="p-2 text-left">Blocked</th>
                    <th class="p-2 text-left">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $row): ?>
                        <tr class='border-t border-gray-700 hover:bg-gray-700'>
                            <td class='p-2'><?= htmlspecialchars($row['UserNum']) ?></td>
                            <td class='p-2'>
                                <a href="view_user.php?usernum=<?= urlencode($row['UserNum']) ?>" class="text-yellow-400 hover:underline">
                                    <?= htmlspecialchars($row['ID']) ?>
                                </a>
                            </td>
                            <td class='p-2'><?= htmlspecialchars($row['CreateDate']) ?></td>
                            <td class='p-2'><?= htmlspecialchars($row['LoginTime']) ?></td>
                            <td class='p-2'><?= htmlspecialchars($row['LastIP']) ?></td>
                            <td class='p-2'>
                                <?= ($row['AuthType'] == 2) ? 'Yes' : 'No' ?><br>
                                <span class="text-xs <?= $row['IsIPBlocked'] ? 'text-red-400' : 'text-green-400' ?>">
                                    STUN: <?= $row['IsIPBlocked'] ? 'Blocked' : 'Allowed' ?>
                                </span>
                            </td>
                            <td class='p-2'>
                                <form action="toggle_block.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="usernum" value="<?= htmlspecialchars($row['UserNum']) ?>">
                                    <input type="hidden" name="auth_type" value="<?= $row['AuthType'] == 2 ? 1 : 2 ?>">
                                    <button type="submit" class="<?= $row['AuthType'] == 2 ? 'bg-green-600' : 'bg-red-600' ?> text-white px-2 py-1 rounded">
                                        <?= $row['AuthType'] == 2 ? 'Unblock' : 'Block' ?>
                                    </button>
                                </form>
                                <form action="toggle_block_ip.php" method="POST" class="inline-block ml-1" onsubmit="return confirm('Stun/unstun this IP?');">
                                    <input type="hidden" name="ip" value="<?= htmlspecialchars($row['LastIP']) ?>">
                                    <input type="hidden" name="is_blocked" value="<?= $row['IsIPBlocked'] ?>">
                                    <button type="submit" class="<?= $row['IsIPBlocked'] ? 'bg-green-700' : 'bg-yellow-500' ?> text-white px-2 py-1 rounded">
                                        <?= $row['IsIPBlocked'] ? 'Unstun IP' : 'Stun IP' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="p-4 text-center text-gray-400">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
