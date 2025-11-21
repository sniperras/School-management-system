<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | University Portal</title>

  <!-- Your Tailwind or CSS build -->
  <link rel="stylesheet" href="index-Dcy6qitJ.css">
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

  <!-- ====== Login Card ====== -->
  <div class="bg-white shadow-xl rounded-xl p-8 w-full max-w-md mx-4">
    <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Login</h2>

    <div class="space-y-4">
      <select id="role" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500">
        <option value="admin">Admin</option>
        <option value="teacher">Teacher</option>
        <option value="student">Student</option>
      </select>

      <input 
        type="text" 
        id="username" 
        placeholder="Username"
        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500"
      >

      <input 
        type="password" 
        id="password" 
        placeholder="Password"
        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500"
      >

      <button 
        id="loginBtn"
        class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition"
      >
        Login
      </button>
    </div>

    <p class="mt-4 text-center text-sm text-gray-500">
      <a href="index.html" class="text-blue-600 hover:underline">← Return to Home</a>
    </p>
  </div>

  <!-- ====== JavaScript (your code) ====== -->
  <script>
    // ====== Modal Controls (not used but required by your script) ======
    const loginModal = null;
    const loginLink = null;
    const getStartedBtn = null;
    const closeModal = null;
    const loginBtn = document.getElementById("loginBtn");

    // Demo credentials
    const credentials = {
      admin: { username: "admin", password: "admin123" },
      teacher: { username: "teacher", password: "teacher123" },
      student: { username: "student", password: "student123" }
    };

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
        window.location.href = `dashboard.html?role=${role}`;
      } else {
        alert("Invalid username or password. Try again.");
      }
    }

    loginBtn.addEventListener("click", login);
  </script>
</body>
</html>
