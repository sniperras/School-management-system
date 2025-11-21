<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Panel | School Management System</title>
  <!-- Use Tailwind build -->
  <link rel="stylesheet" href="index-Dcy6qitJ.css">
</head>
<body class="min-h-screen bg-gray-100">

  <!-- ====== Header ====== -->
  <header class="bg-green-50 py-8 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h1 class="text-3xl font-bold text-gray-900">Student Panel</h1>
      <p class="text-gray-600 mt-2">View attendance, grades, and assignments</p>
    </div>
  </header>

  <!-- ====== Navigation ====== -->
  <nav id="navbar" class="bg-white border-b-2 border-green-200 px-4 sm:px-6 py-3 shadow-sm">
    <!-- Links injected by JS -->
  </nav>

  <!-- ====== Main Content ====== -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

    <!-- Attendance -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Attendance</h2>
      <p class="text-gray-600 mb-4">Check your attendance record.</p>
      <button class="bg-green-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition"
              onclick="alert('Attendance view coming soon!')">View Attendance</button>
    </div>

    <!-- Grades -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Grades</h2>
      <p class="text-gray-600 mb-4">View your grades and report cards.</p>
      <button class="bg-green-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition"
              onclick="alert('Grades view coming soon!')">View Grades</button>
    </div>

    <!-- Assignments -->
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Assignments</h2>
      <p class="text-gray-600 mb-4">Download homework and assignments.</p>
      <button class="bg-green-500 text-white px-4 py-2 rounded-md hover:shadow-lg transition"
              onclick="alert('Assignments feature coming soon!')">View Assignments</button>
    </div>

  </main>

  <!-- ====== Footer ====== -->
  <footer class="bg-gray-50 border-t border-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-center text-gray-600">
      <p>&copy; 2025 School Management System | Student Panel</p>
    </div>
  </footer>

  <!-- ====== Scripts ====== -->
  <script src="script.js"></script>
  <script>
    // Role-based nav injection
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get("role") || "student";

    const navbar = document.getElementById("navbar");
    navbar.innerHTML = `
      <div class="flex items-center space-x-4">
        <a href="dashboard.php?role=${role}" class="text-green-600 font-semibold hover:bg-gray-200 px-3 py-1 rounded-md">Dashboard</a>
        <a href="index.php" class="text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md">Logout</a>
      </div>
    `;
  </script>
</body>
</html>
