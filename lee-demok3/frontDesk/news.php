<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>最新消息｜李武略號殖春公派下網站</title>
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
        <ul id="mainMenu" class="navbar-nav ms-auto align-items-lg-center gap-lg-1 jq-main-menu" data-active="news.php"></ul>
      </div>
    </div>
  </nav>
<main><header class="page-hero">
    <div class="container">
      <p class="eyebrow">Lee Wu-Lue Heritage</p>
      <h1>最新消息</h1>
      <p>李武略號殖春公派下公告列表與資料更新。</p>
    </div>
  </header>
  <section class="section"><div class="container"><div class="section-title" data-aos="fade-up">
      <span>Updates</span>
      <h2>資料更新公告</h2>
      <p>公告列表、來源整理、後台維護狀態。</p>
    </div><div class="content-list"><article class="list-item" data-aos="fade-up"><span>2026-01-15</span><div><h3>李武略資料來源整理</h3><p>新增族譜、祭祀公業、宗祠大埕與古文書線索。</p></div><button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newsModal">詳細</button></article><article class="list-item" data-aos="fade-up"><span>2026-02-15</span><div><h3>族譜資料庫摘要</h3><p>散居地與民國 54 年序等欄位已納入後台規劃。</p></div><button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newsModal">詳細</button></article><article class="list-item" data-aos="fade-up"><span>2026-03-15</span><div><h3>祭祀公業資料</h3><p>統一編號與所在地資料納入公業管理頁。</p></div><button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newsModal">詳細</button></article><article class="list-item" data-aos="fade-up"><span>2026-04-15</span><div><h3>宗祠活動紀錄</h3><p>宗祠大埕與祭祖活動可放入活動花絮。</p></div><button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newsModal">詳細</button></article><article class="list-item" data-aos="fade-up"><span>2026-05-15</span><div><h3>李良經資料欄位</h3><p>本名、號、族譜名稱已加入人物資料卡。</p></div><button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newsModal">詳細</button></article><article class="list-item" data-aos="fade-up"><span>2026-06-15</span><div><h3>高雄田寮族譜線索</h3><p>FamilySearch 目錄列田寮開基祖清李武略，下有五子分五房。</p></div><button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newsModal">詳細</button></article></div></div></section>
  <div class="modal fade" id="newsModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">公告詳細內容</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">此為前端 Modal 示範。正式內容可由 LEE_news 與 newsController 輸出。</div></div></div></div>
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
