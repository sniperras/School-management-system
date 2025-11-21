// ====== Modal Controls ======
const loginModal = document.getElementById("loginModal");
const loginLink = document.getElementById("loginLink");
const getStartedBtn = document.getElementById("getStartedBtn");
const closeModal = document.getElementById("closeModal");
const loginBtn = document.getElementById("loginBtn");

// Demo credentials
const credentials = {
  admin: { username: "admin", password: "admin123" },
  teacher: { username: "teacher", password: "teacher123" },
  student: { username: "student", password: "student123" }
};

// Open modal
function openLogin(e) {
  if (e) e.preventDefault();
  loginModal.style.display = "flex";
}

// Close modal
function closeLogin() {
  loginModal.style.display = "none";
}

// Login action
function login() {
  const role = document.getElementById("role").value;
  const username = document.getElementById("username").value.trim();
  const password = document.getElementById("password").value.trim();

  if (!username || !password) {
    alert("Please enter username and password.");
    return;
  }

  // Validate against demo credentials
  if (
    username === credentials[role].username &&
    password === credentials[role].password
  ) {
    alert(`Logged in as ${role}: ${username}`);
    // Redirect to dashboard with role info in query string
    window.location.href = `dashboard.html?role=${role}`;
  } else {
    alert("Invalid username or password. Try again.");
  }
}

// Event listeners
loginLink.addEventListener("click", openLogin);
getStartedBtn.addEventListener("click", openLogin);
closeModal.addEventListener("click", closeLogin);
loginBtn.addEventListener("click", login);

// Close modal when clicking outside
window.addEventListener("click", (event) => {
  if (event.target === loginModal) {
    closeLogin();
  }
});
