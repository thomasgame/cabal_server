<?php
session_start();
if (!isset($_SESSION['usernum'])) {
    echo "Redirecting to login...";
    header("Location: /login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase History</title>
  <style>
    body {
      background: #111;
      color: #fff;
      font-family: Arial, sans-serif;
      padding: 2rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #222;
    }
    th, td {
      padding: 0.75rem;
      text-align: left;
      border-bottom: 1px solid #444;
    }
    th {
      background: #333;
    }
    tr:hover {
      background: #2c2c2c;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 1rem;
      color: #00aaff;
      text-decoration: none;
    }
  </style>
</head>
<body>

<a class="back-link" href="webshop.php">&larr; Back to Shop</a>

<h1>Purchase History</h1>

<table id="historyTable">
  <thead>
    <tr>
      <th>Item Name</th>
      <th>Quantity</th>
      <th>Price</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
    <tr><td colspan="4">Loading...</td></tr>
  </tbody>
</table>

<script>
  const tableBody = document.querySelector('#historyTable tbody');

  fetch('api/history.php')
    .then(res => res.json())
    .then(data => {
      tableBody.innerHTML = '';

      if (!data.success || !Array.isArray(data.history) || data.history.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4">No history found.</td></tr>';
        return;
      }

      data.history.forEach(entry => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${entry.name}</td>
          <td>${entry.quantity}</td>
          <td>${entry.price}</td>
          <td>${new Date(entry.purchase_date).toLocaleString()}</td>
        `;
        tableBody.appendChild(row);
      });
    })
    .catch(() => {
      tableBody.innerHTML = '<tr><td colspan="4">Failed to load history.</td></tr>';
    });
</script>

</body>
</html>
