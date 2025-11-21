<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>University Portal | School Management System</title>
  <!-- Link Tailwind build -->
  <link rel="stylesheet" href="index-Dcy6qitJ.css">
</head>
<body class="min-h-screen bg-gray-100">

  <!-- ====== Header / Hero ====== -->
  <header class="bg-gradient-to-br from-blue-50 to-indigo-100 py-20 text-center shadow-md">
    <div class="hero-content max-w-3xl mx-auto">
      <h1 class="text-5xl font-bold text-gray-900 mb-4">Welcome to University Portal</h1>
      <p class="text-lg text-gray-600 mb-8">Your gateway to managing Students, Faculty, Classes, and Campus Life</p>
      <a href="#" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition" id="getStartedBtn">Get Started</a>
    </div>
  </header>

  <!-- ====== Navigation ====== -->
  <nav class="bg-white border-b-2 border-blue-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center space-x-4">
      <a href="index.php" class="text-gray-700 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Home</a>
      <a href="#" id="loginLink" class="text-blue-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Login</a>
      <a href="#" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Departments</a>
      <a href="#" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Announcements</a>
      <a href="#" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Contact</a>
    </div>
  </nav>

  <!-- ====== Main Content ====== -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">🎓 Student Services</h2>
      <p class="text-gray-600">Register, track attendance, view grades, and access assignments.</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">👩‍🏫 Faculty Management</h2>
      <p class="text-gray-600">Manage faculty profiles, assign subjects, and monitor performance.</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">🏫 Academic Programs</h2>
      <p class="text-gray-600">Organize classes, subjects, and schedules across departments.</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">📢 Campus Announcements</h2>
      <p class="text-gray-600">Stay updated with the latest news, events, and notifications.</p>
    </div>
  </main>

  <!-- ====== Footer ====== -->
  <footer class="bg-gray-50 border-t border-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-center text-gray-600">
      <p>&copy; 2025 University Portal | All Rights Reserved</p>
    </div>
  </footer>

  <!-- ====== Login Modal ====== -->
  <div id="loginModal" 
     class="fixed inset-0 min-h-screen bg-gray-900/50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-900">Login</h2>
        <button id="closeModal" class="w-8 h-8 rounded-full bg-gray-100 text-gray-700 inline-flex items-center justify-center hover:bg-gray-200 transition">&times;</button>
      </div>
      <div class="space-y-6">
        <select id="role" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="admin">Admin</option>
          <option value="teacher">Teacher</option>
          <option value="student">Student</option>
        </select>
        <input type="text" id="username" placeholder="Username" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="password" id="password" placeholder="Password" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition" id="loginBtn">Login</button>
      </div>
    </div>
  </div>

  <!-- ====== Scripts ====== -->
  <script>
    const loginModal = document.getElementById("loginModal");
const loginLink = document.getElementById("loginLink");
const getStartedBtn = document.getElementById("getStartedBtn");
const closeModal = document.getElementById("closeModal");

// Open modal
function openLogin(e) {
  if (e) e.preventDefault();
  loginModal.classList.remove("hidden");
}

// Close modal
function closeLogin() {
  loginModal.classList.add("hidden");
}

// Event listeners
loginLink.addEventListener("click", openLogin);
getStartedBtn.addEventListener("click", openLogin);
closeModal.addEventListener("click", closeLogin);

// Close when clicking outside
loginModal.addEventListener("click", (event) => {
  if (event.target === loginModal) {
    closeLogin();
  }
});

  </script>
</body>
</html>
