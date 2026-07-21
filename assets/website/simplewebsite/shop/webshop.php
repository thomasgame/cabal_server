<?php
session_start();
if (!isset($_SESSION['usernum'])) {
    header("Location: /login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>WebShop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen bg-cover bg-no-repeat" style="background-image: url('/shop/images/hq720.jpg');">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-yellow-400">Cabal Global Shop</h1>
      <div class="text-lg font-medium">Balance: <span id="userBalance" class="text-green-400">Loading...</span> Cash</div>
	<a href="/php/dashboard.php" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded shadow">
      Back to Dashboard
    </a>
    </div>

    <div class="flex flex-wrap justify-center gap-2 mb-4" id="categoryTabs"></div>
    <div class="flex flex-wrap justify-center gap-2 mb-4" id="subCategoryTabs"></div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="itemGrid">Loading...</div>

    <!-- Modal -->
    <div id="itemModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
      <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg w-full max-w-md mx-auto" id="itemModalContent">
        <h2 id="modalItemName" class="text-xl font-bold mb-2"></h2>
        <p id="modalItemPrice" class="text-yellow-300 font-semibold"></p>
        <p id="modalItemDesc" class="mb-2">No description available.</p>
        <div id="modal-content" class="text-left text-sm mb-4"></div>

        <div class="flex justify-center items-center space-x-2 mb-4">
          <button class="bg-red-500 hover:bg-red-400 text-white px-3 py-1 rounded" onclick="adjustQuantity(-1)">-</button>
          <input id="quantityInput" type="number" min="1" value="1" class="w-16 text-center text-black rounded" />
          <button class="bg-green-500 hover:bg-green-400 text-white px-3 py-1 rounded" onclick="adjustQuantity(1)">+</button>
        </div>

        <button class="bg-blue-600 hover:bg-blue-500 text-white w-full py-2 rounded mb-2" onclick="confirmPurchase()">Confirm Purchase</button>
        <button class="bg-gray-500 hover:bg-gray-400 text-white w-full py-2 rounded" onclick="closeModal()">Cancel</button>
      </div>
    </div>
  </div>

<script>
const USER_NUM = <?php echo json_encode($_SESSION['usernum']); ?>;
let selectedItemId = null;
let selectedQuantity = 1;

function adjustQuantity(amount) {
  selectedQuantity += amount;
  if (selectedQuantity < 1) selectedQuantity = 1;
  document.getElementById('quantityInput').value = selectedQuantity;
}

document.getElementById('quantityInput')?.addEventListener('change', e => {
  const val = parseInt(e.target.value);
  selectedQuantity = isNaN(val) || val < 1 ? 1 : val;
  e.target.value = selectedQuantity;
});

function loadCategories() {
  fetch('api/main_categories.php')
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById('categoryTabs');
      container.innerHTML = '';
      data.categories.forEach((cat, i) => {
        const btn = document.createElement('button');
        btn.textContent = cat.name;
        btn.className = 'bg-gray-700 hover:bg-gray-600 px-4 py-1 rounded' + (i === 0 ? ' bg-blue-600 text-white' : '');
        btn.onclick = () => {
          document.querySelectorAll('#categoryTabs button').forEach(b => b.classList.remove('bg-blue-600', 'text-white'));
          btn.classList.add('bg-blue-600', 'text-white');
          loadSubcategories(cat.id);
        };
        container.appendChild(btn);
        if (i === 0) loadSubcategories(cat.id);
      });
    });
}

function loadSubcategories(mainCategoryId) {
  fetch(`api/subcategories.php?main_category_id=${mainCategoryId}`)
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById('subCategoryTabs');
      container.innerHTML = '';
      if (!data.success || data.subcategories.length === 0) {
        document.getElementById('itemGrid').innerHTML = '<p>No subcategories available.</p>';
        return;
      }
      data.subcategories.forEach((subcat, i) => {
        const btn = document.createElement('button');
        btn.textContent = subcat.name;
        btn.className = 'bg-gray-600 hover:bg-gray-500 px-4 py-1 rounded' + (i === 0 ? ' bg-blue-500 text-white' : '');
        btn.onclick = () => {
          document.querySelectorAll('#subCategoryTabs button').forEach(b => b.classList.remove('bg-blue-500', 'text-white'));
          btn.classList.add('bg-blue-500', 'text-white');
          loadItems(subcat.id);
        };
        container.appendChild(btn);
        if (i === 0) loadItems(subcat.id);
      });
    });
}

function loadItems(subCategoryId) {
  fetch(`api/items_by_category.php?subcategory_id=${subCategoryId}`)
    .then(res => res.json())
    .then(data => {
      const grid = document.getElementById('itemGrid');
      grid.innerHTML = '';
      if (!data.success || !data.items.length) {
        grid.innerHTML = '<p>No items available.</p>';
        return;
      }
      data.items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'bg-gray-800 p-4 rounded-lg shadow text-center hover:scale-105 transition';
        const description = item.description || "No description available.";
        div.innerHTML = `
          <h3 class="text-lg font-semibold mb-2">${item.name}</h3>
          <p class="text-yellow-300 mb-2">${parseInt(item.price).toLocaleString()} Cash</p>
          <button class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1 rounded"
            data-id="${item.id}"
            data-name="${item.name}"
            data-price="${item.price}"
            data-description="${description.replace(/"/g, '&quot;')}"
            data-is-package="${item.is_package ? 1 : 0}"
          >Buy</button>
        `;
        const btn = div.querySelector('button');
        btn.onclick = () => {
          if (btn.dataset.isPackage === '1') {
            loadPackageContents(btn.dataset.id, btn.dataset.name, btn.dataset.price);
          } else {
            showItemDetails(btn.dataset.id, btn.dataset.name, btn.dataset.price, btn.dataset.description);
          }
        };
        grid.appendChild(div);
      });
    });
}

function showItemDetails(id, name, price, description) {
  selectedItemId = id;
  selectedQuantity = 1;
  document.getElementById('modalItemName').textContent = name;
  document.getElementById('modalItemPrice').textContent = `${parseInt(price).toLocaleString()} Cash`;
  document.getElementById('modalItemDesc').innerHTML = description;
  document.getElementById('quantityInput').value = selectedQuantity;
  document.getElementById('modal-content').innerHTML = '';
  document.getElementById('itemModal').classList.remove('hidden');
  document.getElementById('itemModal').classList.add('flex');
}

function loadPackageContents(id, name, price) {
  fetch(`api/package_contents.php?item_id=${id}`)
    .then(res => res.json())
    .then(data => {
      showItemDetails(id, name, price, "<strong>Includes:</strong>");
      const modalContent = document.getElementById("modal-content");
      if (data.success && Array.isArray(data.package_items)) {
        const ul = document.createElement('ul');
        ul.className = 'list-disc pl-5';
        data.package_items.forEach(item => {
          const li = document.createElement('li');
          li.textContent = `${item.item_name} x${item.quantity}`;
          ul.appendChild(li);
        });
        modalContent.appendChild(ul);
      } else {
        modalContent.innerHTML = "<p>Unable to load package contents.</p>";
      }
    });
}

function closeModal() {
  document.getElementById('itemModal').classList.add('hidden');
  document.getElementById('itemModal').classList.remove('flex');
  selectedItemId = null;
  selectedQuantity = 1;
}

function confirmPurchase() {
  if (!selectedItemId || selectedQuantity < 1) return;
  fetch('api/purchase.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      user_num: USER_NUM,
      item_id: selectedItemId,
      quantity: selectedQuantity
    })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.success ? "Purchased!" : data.error);
    if (data.success) loadBalance();
    closeModal();
  });
}

function loadBalance() {
  fetch('api/get_balance.php?user_num=' + USER_NUM)
    .then(res => res.json())
    .then(data => {
      const el = document.getElementById('userBalance');
      if (data.success) {
        el.textContent = parseInt(data.balance.Total).toLocaleString();
      } else {
        el.textContent = "Unavailable";
      }
    });
}

window.onload = () => {
  loadCategories();
  loadBalance();
};
</script>
</body>
</html>
