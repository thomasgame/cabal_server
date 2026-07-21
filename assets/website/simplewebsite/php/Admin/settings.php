<!-- Settings Button -->
<button id="settingsButton" style="position:relative;">Setting ??</button>

<!-- Settings Dropdown Menu (Hidden by default) -->
<div id="settingsMenu" style="
    display: none;
    position: absolute;
    top: 40px;
    right: 0;
    background: #0d0d0d;
    border: 1px solid #555;
    border-radius: 8px;
    width: 200px;
    padding: 10px;
    z-index: 1000;
">
    <ul style="list-style:none; padding:0; margin:0;">
        <li><a href="#" style="color:white; text-decoration:none; display:block; padding:8px 5px;">Change Password</a></li>
        <li><a href="#" style="color:white; text-decoration:none; display:block; padding:8px 5px;">Edit Email</a></li>
        <li><a href="#" style="color:white; text-decoration:none; display:block; padding:8px 5px;">Terms of Service</a></li>
        <li><a href="#" style="color:white; text-decoration:none; display:block; padding:8px 5px;">Privacy Policy</a></li>
        <li><hr style="border:1px solid #333;"></li>
        <li><a href="#" style="color:red; text-decoration:none; display:block; padding:8px 5px;">Log Out</a></li>
    </ul>
</div>

<!-- Small script to toggle dropdown -->
<script>
const settingsButton = document.getElementById('settingsButton');
const settingsMenu = document.getElementById('settingsMenu');

settingsButton.addEventListener('click', () => {
    if (settingsMenu.style.display === 'none') {
        settingsMenu.style.display = 'block';
    } else {
        settingsMenu.style.display = 'none';
    }
});

// Optional: Click outside to close the menu
window.addEventListener('click', function(e){
  if (!settingsButton.contains(e.target) && !settingsMenu.contains(e.target)){
    settingsMenu.style.display = 'none';
  }
});
</script>
