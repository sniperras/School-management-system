<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Panel | School Management System</title>
  <!-- Use Tailwind build -->
  <link rel="stylesheet" href="index-Dcy6qitJ.css">
</head>
<body class="min-h-screen bg-gray-100">

  <!-- ====== Header ====== -->
  <header class="bg-purple-50 py-8 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h1 class="text-3xl font-bold text-gray-900">Teacher Panel</h1>
      <p class="text-gray-600 mt-2">Manage classes, attendance, and grades</p>
    </div>
  </header>

  <!-- ====== Navigation ====== -->
  <nav id="navbar" class="bg-white border-b-2 border-purple-200 px-4 sm:px-6 py-3 shadow-sm">
    <!-- Links injected by JS -->
  </nav>

  <!-- ====== Main Content ====== -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

    <!-- Class Management -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Class Management</h2>
      <p class="text-gray-600 mb-4">View and manage assigned classes.</p>
      <button class="bg-purple-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition"
              onclick="alert('Class management coming soon!')">Go to Classes</button>
    </div>

    <!-- Attendance -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Attendance</h2>
      <p class="text-gray-600 mb-4">Mark and review student attendance.</p>
      <button class="bg-purple-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition"
              onclick="alert('Attendance feature coming soon!')">Go to Attendance</button>
    </div>

    <!-- Grades -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Grades</h2>
      <p class="text-gray-600 mb-4">Upload and manage student grades.</p>
      <button class="bg-purple-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition"
              onclick="alert('Grades feature coming soon!')">Go to Grades</button>
    </div>

  </main>

  <!-- ====== Footer ====== -->
  <footer class="bg-gray-50 border-t border-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-center text-gray-600">
      <p>&copy; 2025 School Management System | Teacher Panel</p>
    </div>
  </footer>

  <!-- ====== Scripts ====== -->
  <script src="script.js"></script>
  <script>
    // Role-based nav injection
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get("role") || "teacher";

    const navbar = document.getElementById("navbar");
    navbar.innerHTML = `
      <div class="flex items-center space-x-4">
        <a href="dashboard.html?role=${role}" class="text-purple-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Dashboard</a>
        <a href="index.html" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Logout</a>
      </div>
    `;
  </script>
</body>
</html>
