<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Advanced Search | School Management System</title>

<div class="min-h-screen bg-gradient-to-br from-cream to-lightblue/20 py-16">
  <div class="max-w-6xl mx-auto px-6">

    <h1 class="text-5xl md:text-6xl font-extrabold text-center text-deepblue mb-4">
      Advanced Search
    </h1>
    <p class="text-center text-xl text-gray-700 mb-12">Find anything on the website instantly</p>

    <!-- Advanced Search Form -->
    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-12">
      <form method="get" action="search.php" class="grid md:grid-cols-4 gap-6">
        <div class="md:col-span-2">
          <input 
            name="q" 
            type="text" 
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
            placeholder="Search keywords..." 
            class="w-full px-6 py-5 rounded-2xl border-2 border-gray-300 focus:border-deepblue focus:ring-4 focus:ring-lightblue/50 transition text-lg"
          >
        </div>

        <div>
          <select name="type" class="w-full px-6 py-5 rounded-2xl border-2 border-gray-300 focus:border-deepblue focus:ring-4 focus:ring-lightblue/50 transition text-lg">
            <option value="">All Types</option>
            <option value="page" <?= ($_GET['type'] ?? '') === 'page' ? 'selected' : '' ?>>Web Pages</option>
            <option value="admission" <?= ($_GET['type'] ?? '') === 'admission' ? 'selected' : '' ?>>Admissions</option>
            <option value="program" <?= ($_GET['type'] ?? '') === 'program' ? 'selected' : '' ?>>Programs</option>
            <option value="contact" <?= ($_GET['type'] ?? '') === 'contact' ? 'selected' : '' ?>>Contact & Info</option>
            <option value="announcement" <?= ($_GET['type'] ?? '') === 'announcement' ? 'selected' : '' ?>>Announcements</option>
          </select>
        </div>

        <div class="flex gap-3">
          <button type="submit" 
                  class="flex-1 bg-gradient-to-r from-deepblue to-midblue text-white font-bold px-8 py-5 rounded-2xl hover:scale-105 transition shadow-xl">
            <i class="fas fa-search mr-3"></i> Search
          </button>
          <a href="search.php" 
             class="bg-gray-200 text-gray-700 px-6 py-5 rounded-2xl hover:bg-gray-300 transition">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </form>
    </div>

    <?php
    $query = trim($_GET['q'] ?? '');
    $type  = $_GET['type'] ?? '';
    $results = [];

    if ($query !== '') {
        // Define pages with categories
        $pages = [
            'index.php'           => ['title' => 'Home',                 'type' => 'page'],
            'admissions.php'      => ['title' => 'Admissions',           'type' => 'admission'],
            'programs.php'        => ['title' => 'Programs & Degrees',   'type' => 'program'],
            'contact_us.php'      => ['title' => 'Contact Us',           'type' => 'contact'],
            'announcements.php'   => ['title' => 'Announcements',        'type' => 'announcement'],
            'alumni.php'          => ['title' => 'Alumni',               'type' => 'page'],
            'login.php'           => ['title' => 'Login',                'type' => 'page'],
            'register.php'        => ['title' => 'Register',             'type' => 'page'],
            'forgot_password.php' => ['title' => 'Forgot Password',     'type' => 'page'],
        ];

        foreach ($pages as $file => $info) {
            if (!file_exists($file)) continue;

            // Apply type filter
            if ($type !== '' && $info['type'] !== $type) continue;

            $content = file_get_contents($file);
            $text = strip_tags($content);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = strtolower($text);
            $searchTerm = strtolower($query);

            if (str_contains($text, $searchTerm)) {
                $pos = strpos($text, $searchTerm);
                $start = max(0, $pos - 100);
                $snippet = substr($text, $start, 250);
                $snippet = '...' . $snippet . '...';

                $snippet = preg_replace(
                    '/' . preg_quote($query, '/') . '/i',
                    '<mark class="bg-yellow-300 text-black px-2 py-1 rounded font-bold">$0</mark>',
                    $snippet
                );

                $results[] = [
                    'title'   => $info['title'],
                    'url'     => $file,
                    'type'    => ucfirst($info['type']),
                    'badge'   => $info['type'],
                    'snippet' => $snippet
                ];
            }
        }
    }
    ?>

    <!-- Results -->
    <?php if ($query === ''): ?>
      <div class="text-center py-32">
        <i class="fas fa-search text-9xl text-gray-300 mb-8"></i>
        <p class="text-3xl text-gray-600">Start typing to search the entire website</p>
      </div>

    <?php elseif (empty($results)): ?>
      <div class="text-center py-32 bg-white rounded-3xl shadow-2xl">
        <i class="fas fa-search-minus text-9xl text-gray-300 mb-8"></i>
        <p class="text-4xl font-bold text-deepblue mb-4">No Results Found</p>
        <p class="text-xl text-gray-600">Try different keywords or remove filters</p>
      </div>

    <?php else: ?>
      <div class="mb-8 text-center">
        <p class="text-2xl font-bold text-deepblue">
          Found <span class="text-midblue"><?= count($results) ?></span> result(s) 
          for "<span class="text-midblue"><?= htmlspecialchars($query) ?></span>"
          <?php if ($type): ?> in <span class="text-midblue"><?= ucfirst($type) ?></span><?php endif; ?>
        </p>
      </div>

      <div class="grid gap-8">
        <?php foreach ($results as $result): ?>
          <a href="<?= $result['url'] ?>" 
             class="block bg-white rounded-3xl shadow-xl overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 border border-gray-100">

            <div class="p-10">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-3xl font-extrabold text-deepblue">
                  <?= htmlspecialchars($result['title']) ?>
                </h3>
                <span class="px-6 py-3 rounded-full text-white font-bold text-sm 
                  <?= $result['badge'] === 'admission' ? 'bg-green-600' : 
                      ($result['badge'] === 'program' ? 'bg-purple-600' : 
                      ($result['badge'] === 'contact' ? 'bg-blue-600' : 
                      ($result['badge'] === 'announcement' ? 'bg-orange-600' : 'bg-gray-600'))) ?>">
                  <?= $result['type'] ?>
                </span>
              </div>

              <p class="text-gray-700 text-lg leading-relaxed mb-6">
                <?= $result['snippet'] ?>
              </p>

              <div class="flex items-center justify-between text-midblue font-bold">
                <span><?= $result['url'] ?></span>
                <span>View Page →</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>