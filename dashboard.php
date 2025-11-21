<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | School Management System</title>
  <!-- Use your Tailwind build or the provided CSS file -->
  <link rel="stylesheet" href="index-Dcy6qitJ.css">
</head>
<body class="min-h-screen bg-gray-100">

  <!-- Header -->
  <header class="bg-blue-50 py-8 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      <p id="welcomeMsg" class="text-gray-600 mt-2">Welcome to your School Management System</p>
    </div>
  </header>

  <!-- Nav -->
  <nav id="navbar" class="bg-white border-b-2 border-blue-200 px-4 sm:px-6 py-3 shadow-sm">
    <!-- Links injected by JS -->
  </nav>

  <!-- Panels -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <!-- Admin Panel -->
    <div id="adminPanel" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Admin Panel</h2>
      <p class="text-gray-600 mb-4">Manage users, classes, and system settings.</p>
      <a href="admin.html?role=admin" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Go to Admin</a>
    </div>

    <!-- Teacher Panel -->
    <div id="teacherPanel" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Teacher Panel</h2>
      <p class="text-gray-600 mb-4">View assigned classes, mark attendance, and upload grades.</p>
      <a href="teacher.html?role=teacher" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition">Go to Teacher</a>
    </div>

    <!-- Student Panel -->
    <div id="studentPanel" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Student Panel</h2>
      <p class="text-gray-600 mb-4">Check attendance, view grades, and download assignments.</p>
      <a href="student.html?role=student" class="bg-green-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition">Go to Student</a>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-50 border-t border-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-center text-gray-600">
      <p>&copy; 2025 School Management System | Dashboard</p>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="script.js"></script>
  <script>
    // ====== Role-based logic ======
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get("role");

    const adminPanel = document.getElementById("adminPanel");
    const teacherPanel = document.getElementById("teacherPanel");
    const studentPanel = document.getElementById("studentPanel");
    const navbar = document.getElementById("navbar");
    const welcomeMsg = document.getElementById("welcomeMsg");

    // Show/hide panels
    if (role === "student") {
      adminPanel.style.display = "none";
      teacherPanel.style.display = "none";
      studentPanel.style.display = "block";
    } else if (role === "teacher") {
      adminPanel.style.display = "none";
      teacherPanel.style.display = "block";
      studentPanel.style.display = "block";
    } else if (role === "admin") {
      adminPanel.style.display = "block";
      teacherPanel.style.display = "block";
      studentPanel.style.display = "block";
    } else {
      adminPanel.style.display = "none";
      teacherPanel.style.display = "none";
      studentPanel.style.display = "none";
    }

    // Build nav dynamically
    let dashboardLink = "";
    if (role === "admin") {
      dashboardLink = '<a href="dashboard.html?role=admin" class="text-blue-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Dashboard</a>';
    } else if (role === "teacher") {
      dashboardLink = '<a href="dashboard.html?role=teacher" class="text-blue-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Dashboard</a>';
    } else if (role === "student") {
      dashboardLink = '<a href="dashboard.html?role=student" class="text-blue-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Dashboard</a>';
    } else {
      dashboardLink = '<a href="dashboard.html" class="text-blue-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Dashboard</a>';
    }

    navbar.innerHTML = `
      <div class="flex items-center space-x-4">
        ${dashboardLink}
        <a href="index.html" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Logout</a>
      </div>
    `;

    // Personalized welcome message
    if (role) {
      welcomeMsg.textContent = `Welcome, ${role.charAt(0).toUpperCase() + role.slice(1)}!`;
    }
  </script>
</body>
</html>
