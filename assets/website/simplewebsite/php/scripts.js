// ======= SETTINGS PANEL TOGGLE =======
document.getElementById('settingsBtn')?.addEventListener('click', () => {
    document.getElementById('settingsPanel')?.classList.add('active');
});
document.getElementById('closeSettings')?.addEventListener('click', () => {
    document.getElementById('settingsPanel')?.classList.remove('active');
});

// ======= FETCH ITEMS =======
let items = [];
fetch('items.php')
    .then(res => res.json())
    .then(data => {
        items = Array.isArray(data) ? data : [];
        console.log("Fetched items:", items);
    })
    .catch(err => console.error("Error fetching items:", err));

// ======= ELEMENT REFERENCES =======
const searchInputs = [
    { input: document.getElementById('searchItemMail'), kindInput: document.getElementById('itemKindIdxMail') },
    { input: document.getElementById('searchItemCash'), kindInput: document.getElementById('itemKindIdxCash') }
];

// ======= DROPDOWN SETUP =======
const dropdown = document.createElement("div");
Object.assign(dropdown.style, {
    position: "absolute",
    backgroundColor: "#1e1e2f",
    border: "1px solid #444",
    borderRadius: "5px",
    boxShadow: "0 2px 5px rgba(0,0,0,0.5)",
    zIndex: "1000",
    maxHeight: "200px",
    overflowY: "auto",
    display: "none",
    fontSize: "14px",
    color: "#fff"
});
document.body.appendChild(dropdown);

// ======= DEBOUNCE FUNCTION =======
function debounce(func, delay = 300) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}

// ======= POSITION DROPDOWN =======
function updateDropdownPosition(targetInput = document.activeElement) {
    const rect = targetInput.getBoundingClientRect();
    dropdown.style.left = `${rect.left + window.scrollX}px`;
    dropdown.style.top = `${rect.bottom + window.scrollY}px`;
    dropdown.style.width = `${rect.width}px`;
}

// ======= SHOW MATCHING ITEMS =======
function showMatches(value, activeInput, activeKindInput) {
    dropdown.innerHTML = '';
    const results = items
        .filter(item => item.name.toLowerCase().includes(value.toLowerCase()))
        .slice(0, 10);

    if (results.length === 0) {
        dropdown.style.display = "none";
        return;
    }

    results.forEach(item => {
        const option = document.createElement("div");
        option.textContent = `${item.name} (${item.id})`;
        Object.assign(option.style, {
            padding: "6px 10px",
            cursor: "pointer",
            borderBottom: "1px solid #333"
        });

        option.addEventListener("mouseover", () => {
            option.style.backgroundColor = "#2a2a3c";
        });
        option.addEventListener("mouseout", () => {
            option.style.backgroundColor = "transparent";
        });

        option.addEventListener("click", () => {
            activeInput.value = item.name;
            if (activeKindInput && activeKindInput.type === 'number') {
                const numericPart = item.id.replace(/\D/g, '');
                activeKindInput.value = numericPart || '';
            }
            dropdown.style.display = "none";
        });

        dropdown.appendChild(option);
    });

    updateDropdownPosition(activeInput);
    dropdown.style.display = "block";
}

// ======= INPUT HANDLERS FOR BOTH FIELDS =======
searchInputs.forEach(({ input, kindInput }) => {
    if (!input) return;

    input.addEventListener("input", debounce(() => {
        const value = input.value.trim();
        if (!value || !items.length) {
            dropdown.style.display = "none";
            return;
        }
        showMatches(value, input, kindInput);
    }));

    // ======= MANUAL SEARCH BUTTON =======
    const searchBtn = document.createElement("button");
    searchBtn.textContent = "Search";
    Object.assign(searchBtn.style, {
        marginLeft: "10px",
        padding: "5px 10px",
        background: "#ffc107",
        border: "none",
        borderRadius: "4px",
        cursor: "pointer",
        fontWeight: "bold"
    });

    input.parentNode.insertBefore(searchBtn, input.nextSibling);

    searchBtn.addEventListener("click", () => {
        const value = input.value.trim();
        if (!value || !items.length) {
            dropdown.style.display = "none";
            return;
        }
        showMatches(value, input, kindInput);
    });
});

// ======= HIDE DROPDOWN ON OUTSIDE CLICK =======
document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target) &&
        !searchInputs.some(({ input }) => input === e.target)) {
        dropdown.style.display = "none";
    }
});

// ======= POSITION ON SCROLL OR RESIZE =======
["scroll", "resize"].forEach(evt => {
    window.addEventListener(evt, () => updateDropdownPosition());
});
