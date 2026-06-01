(function ($) {
  const menuGroups = [
    {
      title: "首頁(前台)",
      href: "index.php",
      module: "Home",
      desc: "主要以 RWD 響應式設計，支援桌機、平板、手機。",
      features: ["橫幅主視覺 (Carousel)", "最新公告摘要", "活動快訊入口"]
    },
    {
      title: "宗親會簡介",
      href: "about.php",
      module: "About",
      desc: "李武略號殖春公派下宗親會簡介內容。",
      features: ["宗旨與理念", "成立沿革", "家族精神與文化", "大事記要"]
    },
    {
      title: "組織章程",
      href: "organization.php",
      module: "Organization",
      desc: "組織、理事會與章程文件管理。",
      features: ["組織架構圖", "理事會成員介紹", "章程文件下載"]
    },
    {
      title: "最新消息",
      href: "news.php",
      module: "News",
      desc: "公告、家族新聞與媒體報導。",
      features: ["公告事項列表 (Cards + Modal 詳細內容)", "家族新聞", "媒體報導"]
    },
    {
      title: "活動花絮",
      href: "events.php",
      module: "Events",
      desc: "年度活動、祭祖影片與文化交流紀錄。",
      features: ["年度大會照片集", "祭祖典禮影片", "文化交流活動紀錄"]
    },
    {
      title: "族譜紀錄",
      href: "genealogy.php",
      module: "Genealogy",
      desc: "李武略派下世系、田寮五房與族譜查詢。",
      features: ["家族世系圖 (互動式樹狀圖)", "祖先事蹟文章", "族譜電子化查詢功能"]
    },
    {
      title: "年會手冊",
      href: "manuals.php",
      module: "Manuals",
      desc: "歷年手冊、會議紀錄與重要決議。",
      features: ["歷年手冊下載 (PDF)", "會議紀錄", "重要決議"]
    },
    {
      title: "祭祀公業",
      href: "ancestral.php",
      module: "Ancestral",
      desc: "祖塔、祠堂、祭祀流程與公業資產。",
      features: ["祖塔與祠堂介紹", "祭祀流程", "公業資產管理資訊"]
    },
    {
      title: "文化傳承",
      href: "culture.php",
      module: "Culture",
      desc: "李氏家族故事、傳統習俗與文獻典藏。",
      features: ["家族故事專區", "傳統習俗介紹", "文獻典藏"]
    },
    {
      title: "其他功能",
      href: "#",
      module: "System",
      desc: "保留既有網頁，不刪除、不移動前台架構。",
      features: [
        { label: "會員專區", href: "members.php", module: "Members" },
        { label: "聯絡我們", href: "contact.php", module: "Contact" },
        { label: "後台管理", href: "admin.php", module: "Admin" }
      ]
    }
  ];

  function getActivePage($menu) {
    const fromData = $menu.data("active");
    if (fromData) return fromData;
    const path = window.location.pathname.split("/").pop();
    return path || "index.php";
  }

  function buildFeatureList(group, activePage) {
    if (group.title === "其他功能") {
      return group.features.map((item) => {
        const active = item.href === activePage ? " active" : "";
        return `<a class="jq-menu-link${active}" href="${item.href}" data-module="${item.module}">
          <span>${item.label}</span><small>${item.module}</small>
        </a>`;
      }).join("");
    }

    const listItems = group.features.map((feature) => `<li>${feature}</li>`).join("");
    return `<ul class="jq-menu-feature-list">${listItems}</ul>
      <a class="jq-menu-link goto-page" href="${group.href}" data-module="${group.module}">
        <span>進入${group.title}</span><small>${group.module}</small>
      </a>`;
  }

  function buildMenu() {
    const $menu = $("#mainMenu");
    if (!$menu.length) return;

    const activePage = getActivePage($menu);
    const html = menuGroups.map((group) => {
      const isOtherActive = group.title === "其他功能" && group.features.some((item) => item.href === activePage);
      const groupActive = group.href === activePage || isOtherActive;
      const panel = buildFeatureList(group, activePage);

      return `<li class="nav-item menu-category${groupActive ? " menu-category-active" : ""}" data-category="${group.title}">
        <button class="nav-link jq-menu-toggle" type="button" aria-expanded="false">
          <span>${group.title}</span><i class="bi bi-chevron-down"></i>
        </button>
        <div class="jq-menu-panel">
          <p>${group.desc}</p>
          <div class="jq-menu-links">${panel}</div>
        </div>
      </li>`;
    }).join("");

    $menu.html(html);
  }

  function bindMenu() {
    const $menu = $("#mainMenu");

    const closePanels = () => {
      $(".menu-category").removeClass("is-open");
      $(".jq-menu-toggle").attr("aria-expanded", "false");
      $(".jq-menu-panel:visible").stop(true, true).fadeOut(120);
    };

    $menu.on("click", ".jq-menu-toggle", function (e) {
      e.preventDefault();
      const $item = $(this).closest(".menu-category");
      const opened = $item.hasClass("is-open");
      closePanels();
      if (!opened) {
        $item.addClass("is-open");
        $(this).attr("aria-expanded", "true");
        $item.find(".jq-menu-panel").stop(true, true).fadeIn(160);
      }
    });

    $menu.on("mouseenter", ".menu-category", function () {
      if (!window.matchMedia("(min-width: 992px)").matches) return;
      closePanels();
      $(this).addClass("is-open").find(".jq-menu-toggle").attr("aria-expanded", "true");
      $(this).find(".jq-menu-panel").stop(true, true).fadeIn(140);
    });

    $menu.on("mouseleave", ".menu-category", function () {
      if (!window.matchMedia("(min-width: 992px)").matches) return;
      $(this).removeClass("is-open").find(".jq-menu-toggle").attr("aria-expanded", "false");
      $(this).find(".jq-menu-panel").stop(true, true).fadeOut(100);
    });

    $(document).on("click", function (e) {
      if (!$(e.target).closest("#mainMenu").length) closePanels();
    });

    $(document).on("keyup", function (e) {
      if (e.key === "Escape") closePanels();
    });
  }

  $(function () {
    buildMenu();
    bindMenu();

    if (window.AOS) AOS.init({ duration: 720, once: true, offset: 80 });

    const $nav = $(".c-main-navbar");
    const $topBtn = $(".to-top");
    const $dot = $(".cursor-dot");

    const onScroll = () => {
      $nav.toggleClass("is-scrolled", window.scrollY > 20);
      $topBtn.toggleClass("show", window.scrollY > 500);
    };
    $(window).on("scroll", onScroll);
    onScroll();

    $topBtn.on("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

    if ($dot.length && window.matchMedia("(pointer:fine)").matches && window.gsap) {
      $(window).on("mousemove", function (e) {
        gsap.to($dot[0], { x: e.clientX, y: e.clientY, duration: 0.22, ease: "power2.out" });
      });
    }

    if (window.gsap) {
      gsap.from(".carousel-caption .eyebrow", { y: 18, opacity: 0, duration: 0.8, delay: 0.25 });
      gsap.from(".carousel-caption h1", { y: 34, opacity: 0, duration: 1, delay: 0.35 });
      gsap.from(".carousel-caption p,.carousel-caption .btn", { y: 18, opacity: 0, duration: 0.8, delay: 0.55, stagger: 0.08 });
    }
  });
})(jQuery);
