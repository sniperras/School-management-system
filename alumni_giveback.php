<?php
// alumni-give-back.php
// Standalone, self-contained alumni donation page using TailwindCSS (CDN) + Font Awesome (CDN)
// Replace placeholder bank/merchant/crypto details with real values before going live.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alumni Give Back | School Management System</title>
  <meta name="description" content="Support your alma mater — donate to scholarships, facilities and student programs.">
  <!-- Tailwind CDN for quick prototyping (replace with compiled CSS in production) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-nx8B1m0K1q6k6K3Yk6Aq6y5k6q6..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* small custom palette for the page */
    :root{
      --deepblue:#0b3b66;
      --midblue:#0766b0;
      --purple:#6b21a8;
      --yellow:#f6c12d;
    }
    .text-deepblue{ color:var(--deepblue); }
    .from-deepblue{ --tw-gradient-from: var(--deepblue); }
    .to-midblue{ --tw-gradient-to: var(--midblue); }
  </style>
</head>
<body class="antialiased bg-white text-gray-800">

  <?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>
    <!-- Mobile menu -->
    <div id="mobileMenu" class="md:hidden hidden px-6 pb-6">
      <a href="#" class="block py-2">Home</a>
      <a href="#bank" class="block py-2">Bank Details</a>
      <a href="#international" class="block py-2">International</a>
      <a href="#crypto" class="block py-2">Crypto</a>
      <a href="#local" class="block py-2">Local</a>
      <a href="alumni_register.php" class="block mt-4 bg-yellow-400 text-deepblue px-4 py-2 rounded-lg text-center font-semibold">Register</a>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="bg-gradient-to-r from-deepblue to-midblue text-white py-20 md:py-28">
    <div class="max-w-5xl mx-auto px-6 text-center">
      <h1 class="text-4xl md:text-6xl font-extrabold mb-4">Support Your Alma Mater</h1>
      <p class="text-lg md:text-2xl opacity-90 max-w-3xl mx-auto">Your contribution helps us provide scholarships, improve facilities, and empower future generations.</p>
      <div class="mt-8">
        <a href="#bank" class="inline-block bg-yellow-400 text-deepblue text-lg font-bold px-8 py-3 rounded-xl hover:bg-yellow-300 transition transform hover:scale-105 shadow">Donate Now</a>
      </div>
    </div>
  </section>

  <!-- Bank Account Information -->
  <section id="bank" class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-deepblue mb-10">Bank Account Details</h2>
      <div class="grid gap-8 md:grid-cols-3">
        <!-- Commercial Bank -->
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <img src="img/cbe-logo.png" alt="Commercial Bank of Ethiopia" class="mx-auto h-16 mb-4">
          <h3 class="text-2xl font-semibold mb-2">Commercial Bank of Ethiopia</h3>
          <p><strong>Account Name:</strong> School Alumni Association</p>
          <p><strong>Account Number:</strong> 100012345678</p>
          <p><strong>SWIFT:</strong> CBETETAA</p>
          <p><strong>Branch:</strong> Addis Ababa Main Branch</p>
        </div>
        <!-- Dashen Bank -->
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <img src="img/dashen-logo.png" alt="Dashen Bank" class="mx-auto h-16 mb-4">
          <h3 class="text-2xl font-semibold mb-2">Dashen Bank</h3>
          <p><strong>Account Name:</strong> School Alumni Association</p>
          <p><strong>Account Number:</strong> 200098765432</p>
          <p><strong>SWIFT:</strong> DASHETAA</p>
          <p><strong>Branch:</strong> Addis Ababa HQ</p>
        </div>
        <!-- Amhara Bank -->
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <img src="img/amhara-logo.png" alt="Amhara Bank" class="mx-auto h-16 mb-4">
          <h3 class="text-2xl font-semibold mb-2">Amhara Bank</h3>
          <p><strong>Account Name:</strong> School Alumni Association</p>
          <p><strong>Account Number:</strong> 300055556666</p>
          <p><strong>SWIFT:</strong> AMHRETAA</p>
          <p><strong>Branch:</strong> Bahir Dar Branch</p>
        </div>
      </div>
    </div>
  </section>

  <!-- International Payment Methods -->
  <section id="international" class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-deepblue mb-10">International Payment Options</h2>
      <div class="grid gap-8 md:grid-cols-3">
        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <i class="fas fa-globe text-4xl text-deepblue mb-4"></i>
          <h3 class="text-xl md:text-2xl font-semibold mb-2">Wire Transfer</h3>
          <p class="text-gray-700">Use the SWIFT codes above for international transfers directly to our bank accounts.</p>
        </div>
        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <i class="fab fa-paypal text-4xl text-deepblue mb-4"></i>
          <h3 class="text-xl md:text-2xl font-semibold mb-2">PayPal</h3>
          <p class="text-gray-700">Send your support via PayPal to <strong>alumni-support@example.com</strong></p>
        </div>
        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <i class="fab fa-stripe text-4xl text-deepblue mb-4"></i>
          <h3 class="text-xl md:text-2xl font-semibold mb-2">Stripe</h3>
          <p class="text-gray-700">Donate securely through Stripe. Merchant ID: <strong>STRIPE-ALUMNI-001</strong></p>
        </div>
      </div>

      <div class="grid gap-8 md:grid-cols-2 mt-10">
        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <i class="fas fa-credit-card text-4xl text-deepblue mb-4"></i>
          <h3 class="text-xl md:text-2xl font-semibold mb-2">Visa / MasterCard</h3>
          <p class="text-gray-700">We accept international card payments. Gateway ID: <strong>CARD-GATEWAY-123</strong></p>
        </div>
        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <i class="fab fa-cc-amex text-4xl text-deepblue mb-4"></i>
          <h3 class="text-xl md:text-2xl font-semibold mb-2">American Express</h3>
          <p class="text-gray-700">Support us using AmEx cards. Merchant ID: <strong>AMEX-ALUMNI-789</strong></p>
        </div>
      </div>
    </div>
  </section>

  <!-- Cryptocurrency Donations -->
  <section id="crypto" class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-deepblue mb-10">Cryptocurrency Donations</h2>
      <div class="grid gap-8 md:grid-cols-4">
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <i class="fab fa-bitcoin text-3xl mb-4" style="color:#f2a900"></i>
          <h3 class="text-xl font-semibold mb-2">Bitcoin</h3>
          <p class="text-gray-700 break-all">Wallet Address: <strong>bc1qexamplebtcaddress12345</strong></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <i class="fab fa-ethereum text-3xl mb-4" style="color:#3c3cbd"></i>
          <h3 class="text-xl font-semibold mb-2">Ethereum</h3>
          <p class="text-gray-700 break-all">Wallet Address: <strong>0xExampleEthereumAddress67890</strong></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <i class="fas fa-coins text-3xl mb-4" style="color:#2ecc71"></i>
          <h3 class="text-xl font-semibold mb-2">USDT (Tether)</h3>
          <p class="text-gray-700 break-all">Wallet Address: <strong>TetherExampleAddressUSDT123</strong></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-md text-center">
          <i class="fab fa-litecoin text-3xl mb-4" style="color:#bfbfbf"></i>
          <h3 class="text-xl font-semibold mb-2">Litecoin</h3>
          <p class="text-gray-700 break-all">Wallet Address: <strong>LTCExampleWalletAddress456</strong></p>
        </div>
      </div>
    </div>
  </section>

  <!-- Local Payment Systems -->
  <section id="local" class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-deepblue mb-10">Popular Local Payment Systems</h2>
      <div class="grid gap-8 md:grid-cols-2">
        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <img src="img/telebirr.png" alt="Telebirr" class="mx-auto h-20 mb-4">
          <h3 class="text-xl font-semibold mb-2">Telebirr</h3>
          <p class="text-gray-700">Send contributions easily using Telebirr. Account Number: <strong>0911222333</strong></p>
        </div>

        <div class="bg-gray-50 p-6 rounded-2xl shadow-md text-center">
          <img src="img/chapa.png" alt="Chapa" class="mx-auto h-20 mb-4">
          <h3 class="text-xl font-semibold mb-2">Chapa</h3>
          <p class="text-gray-700">Support us via Chapa online payments. Merchant ID: <strong>CHAPA12345</strong></p>
        </div>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="py-16 bg-gradient-to-br from-purple-600 to-deepblue text-white text-center">
    <div class="max-w-4xl mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-extrabold mb-4">Together, We Make a Difference</h2>
      <p class="text-lg md:text-xl mb-6">Every contribution strengthens our legacy and supports future students.</p>
      <a href="alumni_register.php" class="inline-block bg-yellow-400 text-deepblue text-lg font-bold px-8 py-3 rounded-xl hover:bg-yellow-300 transition transform hover:scale-105 shadow-2xl">Register as Alumni</a>
    </div>
  </section>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>

  <!-- small accessibility helpers -->
  <script>
    // simple keyboard focus outline for accessibility
    document.addEventListener('keydown', function(e){ if(e.key === 'Tab'){ document.documentElement.classList.add('show-focus'); } });
  </script>
</body>
</html>
