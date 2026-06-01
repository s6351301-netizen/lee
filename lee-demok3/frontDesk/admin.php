<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>後台管理｜李武略號殖春公派下網站</title>
  <meta name="description" content="李武略號殖春公派下宗親會前端練習網站，預留 PHP 與 MySQL 後台維護。">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
  <div class="cursor-dot" aria-hidden="true"></div>
  <nav class="c-main-navbar navbar navbar-default navbar-fixed-top fixed-top navbar-expand-lg navbar-light">
    <div class="container-fluid px-lg-5">
      <a class="navbar-brand brand-mark" href="index.php"><span>李</span><strong>Lee Wu-Lue</strong></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="切換選單">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul id="mainMenu" class="navbar-nav ms-auto align-items-lg-center gap-lg-1 jq-main-menu" data-active="admin.php"></ul>
      </div>
    </div>
  </nav>
<main><header class="page-hero">
    <div class="container">
      <p class="eyebrow">Lee Wu-Lue Heritage</p>
      <h1>後台管理</h1>
      <p>李武略派下網站管理員登入、內容維護。</p>
    </div>
  </header>
  <section class="section"><div class="container"><div class="section-title" data-aos="fade-up">
      <span>Backend</span>
      <h2>後端系統架構</h2>
      <p>後台模組以李武略派下族譜、祭祀公業、文獻與會員維護為核心。</p>
    </div><div class="row g-4"><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>會員管理</h3><p>註冊、登入、權限控管</p><code>backend/memberController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>消息管理</h3><p>新增、修改、刪除最新消息</p><code>backend/newsController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>活動管理</h3><p>活動照片、影片上傳</p><code>backend/eventController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>族譜管理</h3><p>李武略派下世系、田寮五房、族譜查詢</p><code>backend/genealogyController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>手冊管理</h3><p>PDF 上傳、下載</p><code>backend/manualController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>公業管理</h3><p>祭祀公業李武略、宗祠、祖產資料維護</p><code>backend/ancestralController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>文化管理</h3><p>李氏家族故事、習俗、文獻管理</p><code>backend/cultureController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>聯絡表單</h3><p>表單送出、Email 通知</p><code>backend/contactController.php</code></article></div><div class='col-md-6 col-xl-4' data-aos='fade-up'><article class='admin-card'><i class='bi bi-database'></i><h3>系統管理</h3><p>網站設定、日誌紀錄</p><code>backend/systemController.php</code></article></div></div></div></section>
</main>  <footer class="site-footer">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-5">
          <div class="brand-mark footer-brand"><span>李</span><strong>Lee Wu-Lue</strong></div>
          <p>李武略（號殖春，本名李良經）派下資料整理練習網站。頁面先完成前端切版，後續可接 PHP、MySQL、會員與後台管理。</p>
        </div>
        <div class="col-lg-4 footer-links"><a href="about.php">宗親會簡介</a><a href="organization.php">組織章程</a><a href="news.php">最新消息</a><a href="events.php">活動花絮</a><a href="genealogy.php">族譜紀錄</a><a href="manuals.php">年會手冊</a></div>
        <div class="col-lg-3">
          <h6>聯絡資訊</h6>
          <p>臺中市大甲區示範地址<br>04-0000-0000<br>service@example.org</p>
        </div>
      </div>
      <div class="footer-bottom">Copyright © 2026 Lee Wu-Lue Demo. Frontend for PHP and MySQL practice.</div>
    </div>
  </footer>
  <button class="to-top" type="button" aria-label="回到頂端"><i class="bi bi-arrow-up"></i></button>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
