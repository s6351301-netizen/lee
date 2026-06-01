<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>首頁｜李武略號殖春公派下網站</title>
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
        <ul id="mainMenu" class="navbar-nav ms-auto align-items-lg-center gap-lg-1 jq-main-menu" data-active="index.php"></ul>
      </div>
    </div>
  </nav>
<main>
    <section id="homeCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
      <div class="carousel-inner"><div class="carousel-item active">
      <img src="https://images.unsplash.com/photo-1499696010180-025ef6e1a8f9?auto=format&fit=crop&w=1800&q=80" class="d-block w-100" alt="李武略號殖春公派下網站">
      <div class="carousel-caption">
        <span class="eyebrow">Lee Wu-Lue</span>
        <h1>李武略號殖春公派下網站</h1>
        <p>以李武略（號殖春，本名李良經）為核心，整理族譜、祭祀公業、宗祠與派下資料。</p>
        <a class="btn btn-gold" href="about.php">查看簡介</a>
        <a class="btn btn-outline-light" href="genealogy.php">族譜紀錄</a>
      </div>
    </div><div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1510798831971-661eb04b3739?auto=format&fit=crop&w=1800&q=80" class="d-block w-100" alt="從福建、朴子、大甲外水尾到高雄田寮">
      <div class="carousel-caption">
        <span class="eyebrow">Lee Wu-Lue</span>
        <h1>從福建、朴子、大甲外水尾到高雄田寮</h1>
        <p>網路報導與族譜資料提到福建來臺、朴子、大糠榔、大甲外水尾，以及高雄田寮李姓族譜中的李武略開基資料。</p>
        <a class="btn btn-gold" href="about.php">查看簡介</a>
        <a class="btn btn-outline-light" href="genealogy.php">族譜紀錄</a>
      </div>
    </div><div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1528164344705-47542687000d?auto=format&fit=crop&w=1800&q=80" class="d-block w-100" alt="族譜紀錄與祭祀公業">
      <div class="carousel-caption">
        <span class="eyebrow">Lee Wu-Lue</span>
        <h1>族譜紀錄與祭祀公業</h1>
        <p>先建立前端頁面與資料欄位，後續接 PHP、MySQL 與後台管理。</p>
        <a class="btn btn-gold" href="about.php">查看簡介</a>
        <a class="btn btn-outline-light" href="genealogy.php">族譜紀錄</a>
      </div>
    </div></div>
      <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
      <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </section>
    <section class="section"><div class="container"><div class="utility-strip" data-aos="fade-up">
      <form class="site-search"><i class="bi bi-search"></i><input type="search" placeholder="站內搜尋：李武略、殖春、李良經、外水尾、田寮"><button class="btn btn-gold" type="button">搜尋</button></form>
      <div class="quick-links"><a href="genealogy.php">族譜下載</a><a href="ancestral.php">祭祀公業</a><a href="culture.php">李氏文化</a><a href="members.php">派下會員</a></div>
    </div></div></section>
    <section class="section intro-section"><div class="container"><div class="row g-5 align-items-center">
      <div class="col-lg-6" data-aos="fade-right"><div class="section-title" data-aos="fade-up">
      <span>Verified Notes</span>
      <h2>李武略資料摘要</h2>
      <p>目前網路可查資料集中在族譜來源、祭祀公業、宗祠大埕報導與清代契約線索。</p>
    </div><p class="lead">本網站先將資料整理為練習版內容，不把未查證的口述當作定論；可在後台增加來源欄位、可信度與註記。</p><div class="stats-grid"><div><strong>李</strong><span>姓氏統一</span></div><div><strong>5</strong><span>網路來源</span></div><div><strong>50</strong><span>SQL 測試資料</span></div></div></div>
      <div class="col-lg-6" data-aos="fade-left"><img class="feature-img" src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80" alt="李氏宗親活動"></div>
    </div></div></section>
    <section class="section"><div class="container"><div class="section-title" data-aos="fade-up">
      <span>News</span>
      <h2>最新消息摘要</h2>
      <p>公告內容已改為李武略號殖春公派下資料整理方向。</p>
    </div><div class="row g-4"><div class="col-md-6 col-xl-3" data-aos="fade-up">
      <article class="news-card"><span>2026.01.15</span><h3>李氏資料整理 1</h3><p>李武略號殖春公派下公告摘要，未來由後台維護。</p><a href="news.php">閱讀</a></article>
    </div><div class="col-md-6 col-xl-3" data-aos="fade-up">
      <article class="news-card"><span>2026.02.15</span><h3>李氏資料整理 2</h3><p>李武略號殖春公派下公告摘要，未來由後台維護。</p><a href="news.php">閱讀</a></article>
    </div><div class="col-md-6 col-xl-3" data-aos="fade-up">
      <article class="news-card"><span>2026.03.15</span><h3>李氏資料整理 3</h3><p>李武略號殖春公派下公告摘要，未來由後台維護。</p><a href="news.php">閱讀</a></article>
    </div><div class="col-md-6 col-xl-3" data-aos="fade-up">
      <article class="news-card"><span>2026.04.15</span><h3>李氏資料整理 4</h3><p>李武略號殖春公派下公告摘要，未來由後台維護。</p><a href="news.php">閱讀</a></article>
    </div></div></div></section>
    <section class="section alt-section"><div class="container"><div class="section-title" data-aos="fade-up">
      <span>Sources</span>
      <h2>網路資料來源</h2>
      <p>所有李武略相關內容都以可查網路來源建立初稿。</p>
    </div><div class="row g-4">    <div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>田寮李姓族譜</span>
        <h3>FamilySearch 目錄記載《田寮李姓族譜》：田寮開基祖（7世）為清李武略，帝盛公第三子；下有五子，分五房。</h3>
        <a href="https://www.familysearch.org/en/search/catalog/1080758" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div>    <div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>田寮族譜索引</span>
        <h3>查譜網與雲家譜資料亦列《田寮李姓族譜》、李溪泉纂修、1971 年版本，歸屬高雄田寮李氏資料。</h3>
        <a href="https://www.gensbook.com/query/read-305999.html" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div><div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>族譜來源</span>
        <h3>WeRelate 收錄「李武略號殖春公祭祀公業派下子孫系統」，並列 FHL film 1391794 Item 8。</h3>
        <a href="https://www.werelate.org/wiki/Source:%E6%9D%8E%E6%AD%A6%E7%95%A5%E8%99%9F%E6%AE%96%E6%98%A5%E5%85%AC%E7%A5%AD%E7%A5%80%E5%85%AC%E6%A5%AD%E6%B4%BE%E4%B8%8B%E5%AD%90%E5%AD%AB%E7%B3%BB%E7%B5%B1" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div><div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>族譜資料庫</span>
        <h3>族譜資料提到始祖李武略，散居臺中大甲、嘉義朴子等地，並有民國 54 年序。</h3>
        <a href="https://www.zupu.cn/citiao/242794.html" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div><div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>宗祠大埕</span>
        <h3>PeoPo 報導記錄祭祀公業李武略宗祠大埕、從福建來臺、經朴子至大糠榔與大甲外水尾等口述脈絡。</h3>
        <a href="https://www.peopo.org/news/295657" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div><div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>公業資料</span>
        <h3>非營利組織網列出祭祀公業李武略（號殖春）、統一編號 76645678、所在地臺中市。</h3>
        <a href="https://nonprofit.iwiki.tw/63875/" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div><div class="col-md-6 col-xl-4" data-aos="fade-up">
      <article class="source-card">
        <span>古文書線索</span>
        <h3>臺灣原住民族事典搜尋結果摘要中，嘉慶十二年契約文字提到「李靜之孫李武略」。</h3>
        <a href="https://aborgpedia.alcd.center/search-result?cat=34&race=0&start=2080" target="_blank" rel="noopener">查看來源</a>
      </article>
    </div></div></div></section>
    <section class="section alt-section"><div class="container"><div class="section-title" data-aos="fade-up">
      <span>Areas</span>
      <h2>派下地區與資料節點</h2>
      <p>依網路資料先建立大甲、外水尾、大糠榔、朴子、高雄田寮等線索版位，後續由後台維護。</p>
    </div><div class="row g-4"><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>臺中大甲</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>外水尾</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>大糠榔</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>嘉義朴子</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>北部聯絡</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>桃竹苗</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>彰化雲嘉</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>臺南高屏</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>宜花東</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>離島</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>海外</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div><div class="col-sm-6 col-lg-3" data-aos="fade-up">
      <article class="area-card">
        <i class="bi bi-geo-alt"></i>
        <h3>高雄田寮</h3>
        <p>李武略號殖春公派下相關地區與資料整理區塊。</p>
      </article>
    </div></div></div></section>
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
