<?php
require_once '../config/db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Shop</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h1 { margin-bottom: 30px; }
        h2 { margin-top: 40px; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; background: #f9f9f9; }
        input, textarea, select { width: 100%; padding: 6px; margin: 6px 0; }
        button { padding: 8px 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin-bottom: 50px; }
    </style>
</head>
<body>

<h1>CabalShop Admin Panel</h1>

<!-- MAIN CATEGORIES -->
<div class="section">
    <h2>Main Categories</h2>
    <form method="POST" action="api/create_main_category.php">
        <input type="text" name="name" placeholder="Name" required>
        <textarea name="description" placeholder="Description"></textarea>
        <button type="submit">Add Main Category</button>
    </form>
    <table>
    <tr>
        <th>Name</th><th>Description</th><th>Created</th><th>Updated</th><th>Actions</th>
    </tr>
    <?php
    $stmt = $connShop->query("SELECT * FROM main_categories ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$row['id'];
        echo "<tr>
            <td>" . htmlspecialchars($row['name']) . "</td>
            <td>" . htmlspecialchars($row['description']) . "</td>
            <td>" . date('Y-m-d', strtotime($row['created_at'])) . "</td>
            <td>" . date('Y-m-d', strtotime($row['updated_at'])) . "</td>
            <td>
                <a href='api/edit_main_category.php?id=$id'>Edit</a> |
                <a href='api/delete_main_category.php?id=$id' onclick='return confirm(\"Delete this main category?\");'>Delete</a>
            </td>
        </tr>";
    }
    ?>
    </table>
</div>

<!-- SUBCATEGORIES -->
<div class="section">
    <h2>Subcategories</h2>
    <form method="POST" action="api/create_subcategory.php">
        <input type="text" name="name" placeholder="Name" required>
        <textarea name="description" placeholder="Description"></textarea>
        <select name="main_category_id" required>
            <option value="">-- Select Main Category --</option>
            <?php
            $stmt = $connShop->query("SELECT id, name FROM main_categories ORDER BY name");
            while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='{$cat['id']}'>" . htmlspecialchars($cat['name']) . "</option>";
            }
            ?>
        </select>
        <button type="submit">Add Subcategory</button>
    </form>
    <table>
        <tr><th>Name</th><th>Main Category</th><th>Description</th><th>Actions</th></tr>
        <?php
        $sql = "SELECT s.*, m.name AS main_name FROM subcategories s
                JOIN main_categories m ON s.main_category_id = m.id
                ORDER BY s.name";
        $stmt = $connShop->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int)$row['id'];
            echo "<tr>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . htmlspecialchars($row['main_name']) . "</td>
                <td>" . htmlspecialchars($row['description']) . "</td>
                <td>
                    <a href='api/edit_subcategory.php?id=$id'>Edit</a> |
                    <a href='api/delete_subcategory.php?id=$id' onclick='return confirm(\"Delete this subcategory?\");'>Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</div>

<!-- ITEMS -->
<div class="section">
    <h2>Items</h2>
    <form method="POST" action="api/create_item.php">
        <input type="text" name="item_id" placeholder="Item ID" required>
        <input type="text" name="name" placeholder="Name" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input type="number" step="0.01" name="price" placeholder="Price">
        <input type="text" name="item_opt" placeholder="Item Opt">
        <select name="is_package">
            <option value="0">Not a Package</option>
            <option value="1">Is a Package</option>
        </select>
        <input type="date" name="expiration_date">
        <input type="number" name="purchase_limit" placeholder="Purchase Limit">
        <input type="text" name="limit_reset_period" placeholder="Limit Reset Period">
        <select name="subcategory_id" required>
    <option value="">-- Select Subcategory --</option>
    <?php
    $sql = "
        SELECT s.id, s.name AS sub_name, m.name AS main_name
        FROM subcategories s
        JOIN main_categories m ON s.main_category_id = m.id
        ORDER BY m.name, s.name
    ";
    $stmt = $connShop->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = htmlspecialchars($row['id']);
        $subName = htmlspecialchars($row['sub_name']);
        $mainName = htmlspecialchars($row['main_name']);
        echo "<option value='$id'>$subName (Main: $mainName)</option>";
    }
    ?>
</select>

        <button type="submit">Add Item</button>
    </form>
    <table>
    <tr>
        <th>Item ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Is Package</th>
        <th>Subcategory</th>
        <th>Main Category</th>
        <th>Actions</th>
    </tr>
    <?php
    $sql = "
        SELECT TOP 1000 
            i.id, i.item_id, i.name, i.description, i.price, i.is_package,
            sc.name AS subcategory_name,
            mc.name AS main_category_name
        FROM items i
        LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
        LEFT JOIN main_categories mc ON sc.main_category_id = mc.id
        ORDER BY i.created_at DESC
    ";
    $stmt = $connShop->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$row['id'];
        echo "<tr>
            <td>" . htmlspecialchars($row['item_id']) . "</td>
            <td>" . htmlspecialchars($row['name']) . "</td>
            <td>" . htmlspecialchars($row['description']) . "</td>
            <td>" . htmlspecialchars($row['price']) . "</td>
            <td>" . ($row['is_package'] ? "Yes" : "No") . "</td>
            <td>" . htmlspecialchars($row['subcategory_name'] ?? '-') . "</td>
            <td>" . htmlspecialchars($row['main_category_name'] ?? '-') . "</td>
            <td>
                <a href='api/edit_item.php?id=$id'>Edit</a> |
                <a href='api/delete_item.php?id=$id' onclick='return confirm(\"Delete this item?\");'>Delete</a>
            </td>
        </tr>";
    }
    ?>
</table>
</div>

<!-- PACKAGES -->
<div class="section">
    <h2>Packages</h2>
    <form method="POST" action="api/create_package.php">
        <input type="text" name="name" placeholder="Package Name" required>
        <select name="is_active">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
        <button type="submit">Add Package</button>
    </form>
    <table>
        <tr><th>Package ID</th><th>Name</th><th>Active</th><th>Actions</th></tr>
        <?php
        $stmt = $connShop->query("SELECT TOP 1000 * FROM packages ORDER BY package_id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int)$row['package_id'];
            echo "<tr>
                <td>$id</td>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . ($row['is_active'] ? "Yes" : "No") . "</td>
                <td>
                    <a href='api/edit_package.php?id=$id'>Edit</a> |
                    <a href='api/delete_package.php?id=$id' onclick='return confirm(\"Delete this package?\");'>Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</div>

<!-- PACKAGE ITEMS -->
<div class="section">
    <h2>Package Items</h2>
    <form method="POST" action="api/create_package_item.php">
        <select name="package_id" required>
            <option value="">-- Select Package --</option>
            <?php
            $stmt = $connShop->query("SELECT package_id, name FROM packages ORDER BY name");
            while ($pkg = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='{$pkg['package_id']}'>" . htmlspecialchars($pkg['name']) . "</option>";
            }
            ?>
        </select>
        <input type="text" name="item_id" placeholder="Item ID" required>
        <input type="number" name="quantity" placeholder="Quantity" required>
        <input type="text" name="item_opt" placeholder="Item Opt">
        <input type="text" name="name" placeholder="Package Item Name" required>
        <button type="submit">Add Package Item</button>
    </form>
    <table>
        <tr><th>Package ID</th><th>Item ID</th><th>Quantity</th><th>Item Name</th><th>Actions</th></tr>
        <?php
        $stmt = $connShop->query("SELECT TOP 1000 * FROM package_items ORDER BY id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int)$row['id'];
            echo "<tr>
                <td>" . htmlspecialchars($row['package_id']) . "</td>
                <td>" . htmlspecialchars($row['item_id']) . "</td>
                <td>" . htmlspecialchars($row['quantity']) . "</td>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>
                    <a href='api/edit_package_item.php?id=$id'>Edit</a> |
                    <a href='api/delete_package_item.php?id=$id' onclick='return confirm(\"Delete this package item?\");'>Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</div>

</body>
</html>
