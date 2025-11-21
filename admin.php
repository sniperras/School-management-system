<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel | School Management System</title>
  <!-- Use Tailwind build -->
  <link rel="stylesheet" href="index-Dcy6qitJ.css">
</head>
<body class="min-h-screen bg-gray-100">

  <!-- ====== Header ====== -->
  <header class="bg-blue-50 py-8 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h1 class="text-3xl font-bold text-gray-900">Admin Panel</h1>
      <p class="text-gray-600 mt-2">Manage users, classes, and system settings</p>
    </div>
  </header>

  <!-- ====== Navigation ====== -->
  <nav id="navbar" class="bg-white border-b-2 border-blue-200 px-4 sm:px-6 py-3 shadow-sm">
    <!-- Links injected by JS -->
  </nav>

  <!-- ====== Main Content ====== -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

    <!-- Manage Users -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Manage Users</h2>
      <p class="text-gray-600 mb-4">Add, edit, or remove users (students, teachers, admins).</p>
      <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
              onclick="alert('User management feature coming soon!')">Go to Users</button>
    </div>

    <!-- Manage Classes -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Manage Classes</h2>
      <p class="text-gray-600 mb-4">Create new classes, assign teachers, and enroll students.</p>
      <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
              onclick="alert('Class management feature coming soon!')">Go to Classes</button>
    </div>

    <!-- System Settings -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">System Settings</h2>
      <p class="text-gray-600 mb-4">Configure school year, grading policies, and system preferences.</p>
      <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
              onclick="alert('System settings feature coming soon!')">Go to Settings</button>
    </div>

  </main>

  <!-- ====== Footer ====== -->
  <footer class="bg-gray-50 border-t border-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-center text-gray-600">
      <p>&copy; 2025 School Management System | Admin Panel</p>
    </div>
  </footer>

  <!-- ====== Scripts ====== -->
  <script src="script.js"></script>
  <script>
    // Get role from query string
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get("role");

    const navbar = document.getElementById("navbar");

    // Build nav based on role
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

    // Inject links into nav
    navbar.innerHTML = `
      <div class="flex items-center space-x-4">
        ${dashboardLink}
        <a href="admin.html" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Admin Panel</a>
        <a href="index.html" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Logout</a>
      </div>
    `;
  </script>
</body>
</html>
