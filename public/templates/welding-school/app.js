(function () {
  "use strict";

  const backend = window.WeldingSchool || {};
  const branding = {
    name: backend.branding?.name || "ALPHA ACADEMY",
    service: backend.branding?.service || "WELDING SCHOOL",
    company: backend.branding?.company || "PT. ALPHA TEKNIK PRATAMA",
    tagline:
      backend.branding?.tagline || "Kompeten. Tersertifikasi. Siap Kerja.",
    logo:
      backend.branding?.logo ||
      "assets/images/alpha-teknik-pratama-logo-hd.png",
  };
  const administrationFee = Math.max(
    0,
    Number(backend.billing?.administrationFee || 0),
  );

  let programs = [
    {
      id: "smaw",
      code: "SMAW",
      title: "SMAW Welder",
      subtitle: "Shielded Metal Arc Welding",
      description:
        "Program intensif untuk menguasai teknik las elektroda terbungkus dari fondasi keselamatan hingga pengujian kompetensi.",
      duration: "80 Jam",
      theory: "20 Jam",
      practice: "60 Jam",
      seats: 12,
      price: 3500000,
      level: "Pemula",
      positions: ["1G", "2G", "3G", "4G"],
      imagePosition: "68% center",
      popular: true,
    },
    {
      id: "fcaw",
      code: "FCAW",
      title: "FCAW Welder",
      subtitle: "Flux Cored Arc Welding",
      description:
        "Pelatihan pengelasan produktivitas tinggi untuk kebutuhan fabrikasi, konstruksi, dan pekerjaan lapangan.",
      duration: "80 Jam",
      theory: "20 Jam",
      practice: "60 Jam",
      seats: 12,
      price: 3800000,
      level: "Menengah",
      positions: ["1G", "2G", "3G"],
      imagePosition: "79% center",
    },
    {
      id: "gtaw",
      code: "GTAW",
      title: "GTAW Welder",
      subtitle: "Gas Tungsten Arc Welding",
      description:
        "Penguasaan teknik TIG untuk hasil presisi tinggi pada carbon steel dan stainless steel.",
      duration: "80 Jam",
      theory: "20 Jam",
      practice: "60 Jam",
      seats: 10,
      price: 4000000,
      level: "Lanjutan",
      positions: ["1G", "2G", "5G", "6G"],
      imagePosition: "88% center",
    },
    {
      id: "gmaw",
      code: "GMAW",
      title: "GMAW Welder",
      subtitle: "Gas Metal Arc Welding",
      description:
        "Pelatihan pengelasan MIG/MAG untuk manufaktur, otomotif, dan fabrikasi modern.",
      duration: "80 Jam",
      theory: "20 Jam",
      practice: "60 Jam",
      seats: 12,
      price: 3800000,
      level: "Menengah",
      positions: ["1G", "2G", "3G"],
      imagePosition: "84% center",
    },
    {
      id: "wqt",
      code: "WQT",
      title: "Welder Qualification Test",
      subtitle: "Uji Kompetensi Welder",
      description:
        "Paket persiapan dan pelaksanaan uji kompetensi bagi welder berpengalaman.",
      duration: "16 Jam",
      theory: "4 Jam",
      practice: "12 Jam",
      seats: 8,
      price: 2000000,
      level: "Lanjutan",
      positions: ["3G", "4G", "6G"],
      imagePosition: "73% center",
    },
    {
      id: "inspector",
      code: "WID",
      title: "Welding Inspector Dasar",
      subtitle: "Basic Welding Inspector",
      description:
        "Program fondasi inspeksi visual, pemahaman WPS, dan dokumentasi kualitas pengelasan.",
      duration: "40 Jam",
      theory: "28 Jam",
      practice: "12 Jam",
      seats: 15,
      price: 3000000,
      level: "Pemula",
      positions: ["Visual", "WPS", "Report"],
      imagePosition: "91% center",
    },
  ];

  const databaseProgramCodes = {
    smaw: "SMAW-3G",
    fcaw: "FCAW-3G",
    gtaw: "GTAW-6G",
    gmaw: "GMAW-2G",
    wqt: "WQT-6G",
    inspector: "WI-BASIC",
  };
  const catalogPrograms = backend.catalog?.programs || [];
  programs.forEach((program) => {
    const databaseProgram = catalogPrograms.find(
      (item) => item.code === databaseProgramCodes[program.id],
    );
    if (!databaseProgram) return;
    program.databaseId = databaseProgram.id;
    program.databaseCode = databaseProgram.code;
    program.databaseBatches = databaseProgram.batches || [];
  });
  if (catalogPrograms.length) {
    programs = programs.filter((program) => program.databaseId);
    catalogPrograms.forEach((databaseProgram) => {
      if (
        programs.some(
          (program) => Number(program.databaseId) === Number(databaseProgram.id),
        )
      ) {
        return;
      }

      programs.push({
        id: `database-program-${databaseProgram.id}`,
        code: databaseProgram.code,
        title: databaseProgram.title,
        subtitle: databaseProgram.category,
        description:
          "Program pelatihan terstruktur untuk meningkatkan kompetensi, keselamatan, dan kesiapan kerja di industri pengelasan.",
        duration: `${databaseProgram.duration_hours} Jam`,
        theory: "Materi kelas",
        practice: "Praktik workshop",
        seats: 12,
        price: Number(databaseProgram.price),
        level: "Pemula",
        positions: ["Teori", "Praktik", "Evaluasi"],
        imagePosition: "75% center",
        databaseId: databaseProgram.id,
        databaseCode: databaseProgram.code,
        databaseBatches: databaseProgram.batches || [],
      });
    });
  }

  const fallbackBatches = [
    {
      id: "batch-2608",
      label: "Batch Agustus 2026",
      start: "10 Agustus 2026",
      end: "21 Agustus 2026",
      schedule: "Senin–Jumat, 08.00–16.00 WIB",
      location: "Workshop Cilegon · Area 1",
      seatsLeft: 5,
      recommended: true,
    },
    {
      id: "batch-2609",
      label: "Batch September 2026",
      start: "7 September 2026",
      end: "18 September 2026",
      schedule: "Senin–Jumat, 08.00–16.00 WIB",
      location: "Workshop Cilegon · Area 2",
      seatsLeft: 9,
    },
    {
      id: "batch-weekend",
      label: "Batch Weekend September",
      start: "5 September 2026",
      end: "4 Oktober 2026",
      schedule: "Sabtu–Minggu, 08.00–16.00 WIB",
      location: "Workshop Cilegon · Area 1",
      seatsLeft: 3,
    },
  ];

  function catalogDate(value) {
    if (!value) return "Belum ditentukan";
    return new Intl.DateTimeFormat("id-ID", {
      day: "numeric",
      month: "long",
      year: "numeric",
      timeZone: "UTC",
    }).format(new Date(`${value}T00:00:00Z`));
  }

  function programBatches(program) {
    if (!program?.databaseBatches?.length) return fallbackBatches;

    return program.databaseBatches.map((batch, index) => ({
      id: `database-batch-${batch.id}`,
      databaseId: batch.id,
      code: batch.code,
      label: batch.name,
      start: catalogDate(batch.start_date),
      end: catalogDate(batch.end_date),
      schedule: "Jadwal pelatihan sesuai informasi dari admin",
      location: "Workshop PT. Alpha Teknik Pratama",
      seatsLeft: Math.max(
        0,
        Number(batch.capacity) - Number(batch.applications_count || 0),
      ),
      recommended: index === 0,
    }));
  }

  let batches = programBatches(programs[0]);

  const academyNews = [
    {
      id: "uji-kompetensi-smaw",
      category: "Kegiatan Akademi",
      date: "2 Agustus 2026",
      title: "24 peserta menyelesaikan uji kompetensi SMAW posisi 3G",
      excerpt:
        "Rangkaian evaluasi praktik, inspeksi visual, dan pengujian hasil las menutup pelatihan intensif Batch Juli.",
      featured: true,
      imagePosition: "72% center",
    },
    {
      id: "safety-induction",
      category: "Safety",
      date: "28 Juli 2026",
      title: "Safety induction membuka program pelatihan Batch Agustus",
      excerpt:
        "Peserta memulai perjalanan belajar dengan pengenalan budaya K3, APD, dan tata kerja workshop.",
      imagePosition: "42% center",
    },
    {
      id: "industry-sharing",
      category: "Kolaborasi Industri",
      date: "21 Juli 2026",
      title: "Praktisi fabrikasi berbagi kebutuhan kompetensi welder terkini",
      excerpt:
        "Sesi industry sharing membantu peserta memahami standar kualitas, disiplin kerja, dan peluang karier.",
      imagePosition: "84% center",
    },
    {
      id: "alumni-day",
      category: "Alumni",
      date: "12 Juli 2026",
      title: "Alumni Day 2026 mempertemukan lulusan dan mitra recruiter",
      excerpt:
        "Forum jejaring perdana membuka akses mentoring, informasi lowongan, dan pembaruan profil kompetensi.",
      imagePosition: "60% center",
    },
    {
      id: "open-house",
      category: "Event",
      date: "5 Juli 2026",
      title: "Open House Workshop: melihat langsung proses belajar di Alpha Academy",
      excerpt:
        "Calon peserta dan keluarga diajak menjelajahi fasilitas, bertemu instruktur, dan mencoba simulasi dasar.",
      imagePosition: "92% center",
    },
  ];

  const steps = [
    { id: "home", label: "Beranda" },
    { id: "about", label: "Tentang Kami" },
    { id: "programs", label: "Program Publik" },
    { id: "news", label: "Berita & Event" },
    { id: "article", label: "Artikel" },
    { id: "alumni", label: "Platform Alumni" },
    { id: "recruiters", label: "Platform Recruiter" },
    { id: "certificate", label: "Verifikasi Sertifikat" },
    { id: "account", label: "Akun" },
    { id: "member-programs", label: "Dashboard Peserta" },
    { id: "detail", label: "Detail" },
    { id: "batch", label: "Batch" },
    { id: "registration", label: "Data Peserta" },
    { id: "documents", label: "Dokumen" },
    { id: "summary", label: "Ringkasan" },
    { id: "invoice", label: "Invoice" },
    { id: "payment", label: "Pembayaran" },
    { id: "success", label: "Berhasil" },
    { id: "dashboard", label: "Dashboard Peserta" },
    { id: "verification", label: "Verifikasi Email" },
  ];

  const savedParticipantSidebar =
    window.localStorage.getItem("welding-participant-sidebar");
  const state = {
    step: 0,
    loggedIn: Boolean(backend.auth?.authenticated),
    dashboardView: "home",
    trainingSection: "overview",
    programOrigin: "public",
    selectedProgram: programs[0],
    selectedBatch: batches[0],
    accountMode: "login",
    applicationStatus: "not-started",
    applications: [],
    application: null,
    invoice: null,
    enrollment: null,
    dashboardSidebarCollapsed: savedParticipantSidebar === "collapsed",
    dashboardSidebarOpen: false,
    profileContinueToRegistration: false,
    paymentMethod: "va-bca",
    uploadedFiles: {},
    search: "",
    level: "Semua Level",
    selectedArticle: academyNews[0],
    verification: {
      pending: Boolean(backend.verification?.pending),
      email: backend.verification?.email || "",
      debugCode: "",
    },
    registration: {
      username: backend.auth?.user?.username || "",
      fullName: backend.auth?.user?.profile?.full_name || "",
      email: backend.auth?.user?.email || "",
      avatar: backend.auth?.user?.avatar || "",
      profileComplete: Boolean(backend.auth?.user?.profile?.complete),
      phone: backend.auth?.user?.profile?.phone || "",
      identityType: backend.auth?.user?.profile?.identity_type || "",
      identityNumber: backend.auth?.user?.profile?.identity_number || "",
      birthPlace: backend.auth?.user?.profile?.birth_place || "",
      birthDate: backend.auth?.user?.profile?.birth_date || "",
      gender: backend.auth?.user?.profile?.gender || "",
      address: backend.auth?.user?.profile?.address || "",
      city: backend.auth?.user?.profile?.city || "",
      province: backend.auth?.user?.profile?.province || "",
      postalCode: backend.auth?.user?.profile?.postal_code || "",
      education: backend.auth?.user?.profile?.last_education || "",
      occupation: backend.auth?.user?.profile?.occupation || "",
      experience: "",
      emergencyName:
        backend.auth?.user?.profile?.emergency_contact_name || "",
      emergencyPhone:
        backend.auth?.user?.profile?.emergency_contact_phone || "",
    },
  };

  const app = document.getElementById("app");
  const toast = document.getElementById("toast");
  let toastTimer;
  let invoiceTimer;

  function money(value) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(value);
  }

  function formatDateTime(value) {
    if (!value) return "-";

    return new Intl.DateTimeFormat("id-ID", {
      dateStyle: "long",
      timeStyle: "short",
      timeZone: "Asia/Jakarta",
    }).format(new Date(value));
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function brandMarkup() {
    return `
      <span class="brand__mark" aria-hidden="true"><img src="${escapeHtml(branding.logo)}" alt=""></span>
      <span class="brand__copy"><strong>${escapeHtml(branding.name)}</strong><small>${escapeHtml(branding.service)}</small></span>
    `;
  }

  function showToast(message, tone) {
    window.clearTimeout(toastTimer);
    toast.className = `toast ${tone ? `toast--${tone}` : ""}`;
    toast.textContent = message;
    toast.hidden = false;
    toastTimer = window.setTimeout(() => {
      toast.hidden = true;
    }, 3200);
  }

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
  }

  async function backendRequest(url, payload = {}) {
    if (!url) {
      throw new Error("Endpoint autentikasi belum tersedia.");
    }

    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken(),
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
      const validationMessage = Object.values(result.errors || {})
        .flat()
        .find(Boolean);
      const error = new Error(
        validationMessage ||
          result.message ||
          "Permintaan tidak dapat diproses. Silakan coba kembali.",
      );
      error.data = result;
      error.status = response.status;
      throw error;
    }

    return result;
  }

  async function backendGet(url) {
    if (!url) throw new Error("Endpoint belum tersedia.");

    const response = await fetch(url, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(result.message || "Data tidak dapat dimuat.");
    }

    return result;
  }

  async function backendFormRequest(url, formData) {
    if (!url) throw new Error("Endpoint pendaftaran belum tersedia.");

    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN": csrfToken(),
      },
      body: formData,
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
      const validationMessage = Object.values(result.errors || {})
        .flat()
        .find(Boolean);
      throw new Error(
        validationMessage ||
          result.message ||
          "Pendaftaran tidak dapat dikirim.",
      );
    }

    return result;
  }

  function applyParticipantProfile(profile = {}) {
    state.registration.username = profile.username || "";
    state.registration.fullName = profile.full_name || "";
    state.registration.email = profile.email || state.registration.email;
    state.registration.profileComplete = Boolean(profile.complete);
    state.registration.phone = profile.phone || "";
    state.registration.identityType = profile.identity_type || "";
    state.registration.identityNumber = profile.identity_number || "";
    state.registration.birthPlace = profile.birth_place || "";
    state.registration.birthDate = profile.birth_date || "";
    state.registration.gender = profile.gender || "";
    state.registration.address = profile.address || "";
    state.registration.city = profile.city || "";
    state.registration.province = profile.province || "";
    state.registration.postalCode = profile.postal_code || "";
    state.registration.education = profile.last_education || "";
    state.registration.occupation = profile.occupation || "";
    state.registration.emergencyName =
      profile.emergency_contact_name || "";
    state.registration.emergencyPhone =
      profile.emergency_contact_phone || "";
  }

  function participantDisplayName() {
    return (
      state.registration.fullName ||
      state.registration.username ||
      backend.auth?.user?.name ||
      "Peserta"
    );
  }

  function setAuthenticatedUser(user) {
    state.loggedIn = true;
    if (user?.email) state.registration.email = user.email;
    state.registration.avatar = user?.avatar || "";
    applyParticipantProfile(
      user?.profile || {
        username: user?.username || "",
        email: user?.email || "",
      },
    );
  }

  function continueAfterAuthentication(result) {
    if (result?.redirect_to || result?.user?.is_admin) {
      window.location.assign(result.redirect_to || backend.routes?.admin || "/admin");
      return true;
    }

    return false;
  }

  function selectApplication(application) {
    if (!application) {
      state.application = null;
      state.applicationStatus = "not-started";
      state.invoice = null;
      state.enrollment = null;
      return;
    }

    state.application = application;
    state.applicationStatus = application.status;
    state.invoice = application.invoice || null;
    state.enrollment = application.enrollment || null;

    const program = programs.find(
      (item) => Number(item.databaseId) === Number(application.training_program_id),
    );
    if (program) {
      state.selectedProgram = program;
      batches = programBatches(program);
      state.selectedBatch =
        batches.find(
          (batch) =>
            Number(batch.databaseId) === Number(application.training_batch_id),
        ) || batches[0];
    }
  }

  function applyApplication(application) {
    if (!application) {
      selectApplication(null);
      return;
    }

    const existingIndex = state.applications.findIndex(
      (item) => Number(item.id) === Number(application.id),
    );
    if (existingIndex >= 0) {
      state.applications[existingIndex] = application;
    } else {
      state.applications.unshift(application);
    }

    selectApplication(application);
  }

  function applyApplications(applications) {
    const selectedId = state.application?.id;
    state.applications = Array.isArray(applications) ? applications : [];
    const selected =
      state.applications.find(
        (application) => Number(application.id) === Number(selectedId),
      ) || state.applications[0];

    selectApplication(selected || null);
  }

  function applicationForProgram(program) {
    return state.applications.find(
      (application) =>
        Number(application.training_program_id) === Number(program?.databaseId),
    );
  }

  async function loadCurrentApplication(showMessage = false) {
    if (!state.loggedIn || backend.auth?.user?.is_admin) return;

    try {
      const result = await backendGet(backend.routes?.applicationCurrent);
      applyApplications(
        result.applications ||
          (result.application ? [result.application] : []),
      );
      render();
      if (showMessage) {
        showToast(
          result.application
            ? "Status pendaftaran berhasil diperbarui."
            : "Belum ada pendaftaran yang dikirim.",
          "info",
        );
      }
    } catch (error) {
      if (showMessage) showToast(error.message, "danger");
    }
  }

  function beginEmailVerification(result) {
    state.loggedIn = false;
    state.verification.pending = true;
    state.verification.email = result.email || state.registration.email;
    state.verification.debugCode = result.debug_code || "";
    navigate("verification");
    showToast(result.message || "Kode verifikasi telah dikirim.", "success");
  }

  function participantInitials() {
    const words = participantDisplayName().trim().split(/\s+/).filter(Boolean);
    const first = words[0]?.charAt(0) || "P";
    const last = words.length > 1 ? words.at(-1).charAt(0) : "";

    return `${first}${last}`.toUpperCase();
  }

  function userAvatar(className = "avatar") {
    const image = state.registration.avatar
      ? `<img data-user-avatar src="${escapeHtml(state.registration.avatar)}" alt="" referrerpolicy="no-referrer">`
      : "";

    return `<span class="${className} user-avatar" aria-label="Foto profil ${escapeHtml(participantDisplayName())}">${participantInitials()}${image}</span>`;
  }

  function dashboardAccountMenu() {
    return `
      <div class="dashboard-account">
        <span>${escapeHtml(state.registration.email)}</span>
        <div class="profile-menu">
          <button
            class="profile-menu__trigger"
            data-action="toggle-profile-menu"
            type="button"
            aria-label="Buka menu profil"
            aria-haspopup="menu"
            aria-expanded="false"
          >
            ${userAvatar()}
            <span class="profile-menu__chevron" aria-hidden="true">⌄</span>
          </button>
          <div class="profile-dropdown" role="menu" hidden>
            <div class="profile-dropdown__identity">
              ${userAvatar("profile-dropdown__avatar")}
              <div>
                <strong>${escapeHtml(state.registration.username ? `@${state.registration.username}` : participantDisplayName())}</strong>
                <small>${escapeHtml(state.registration.email)}</small>
              </div>
            </div>
            <div class="profile-dropdown__divider"></div>
            <button class="profile-dropdown__item" data-action="show-dashboard-profile" role="menuitem" type="button">
              <span aria-hidden="true">●</span>
              Profil &amp; Data Diri
            </button>
            <div class="profile-dropdown__divider"></div>
            <button class="profile-dropdown__logout" data-action="logout" role="menuitem" type="button">
              <span aria-hidden="true">↗</span>
              Logout
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function dashboardHeaderActions() {
    const expanded = !state.dashboardSidebarCollapsed;
    return `
      <div class="dashboard-header-actions">
        <button
          class="dashboard-sidebar-toggle"
          data-action="toggle-dashboard-sidebar"
          type="button"
          aria-label="${expanded ? "Ciutkan sidebar" : "Buka sidebar"}"
          aria-expanded="${expanded}"
        ><span aria-hidden="true">${expanded ? "«" : "»"}</span></button>
        ${dashboardAccountMenu()}
      </div>
    `;
  }

  function dashboardShellClasses(extraClasses = "") {
    return [
      "dashboard-preview",
      state.dashboardSidebarCollapsed ? "is-sidebar-collapsed" : "",
      state.dashboardSidebarOpen ? "is-sidebar-open" : "",
      extraClasses,
    ]
      .filter(Boolean)
      .join(" ");
  }

  function participantSidebar(activeSection) {
    const hasEnrollment = state.applications.some(
      (application) => application.enrollment?.status === "active",
    );
    const hasPayments = state.applications.some(
      (application) => application.invoice,
    );
    const participantLabel = hasEnrollment
      ? "Peserta Aktif"
      : state.applications.length
        ? "Calon Peserta"
        : "Akun Peserta";
    const item = (section, action, icon, label, available = true) => `
      <button
        class="${activeSection === section ? "is-active" : ""}"
        ${available ? `data-action="${action}"` : "disabled"}
        type="button"
      >
        <span aria-hidden="true">${icon}</span>
        <span class="dashboard-nav-label">${label}</span>
      </button>
    `;

    return `
      <aside class="dashboard-sidebar">
        <div class="brand">
          ${brandMarkup()}
        </div>
        <div class="participant-profile">${userAvatar("participant-avatar")}<div><strong>${escapeHtml(participantDisplayName())}</strong><small>${participantLabel}</small></div></div>
        <nav aria-label="Menu peserta">
          ${item("home", "show-dashboard-home", "⌂", "Dashboard")}
          ${item("programs", "show-dashboard-programs", "▤", "Program Pelatihan")}
          ${item("applications", "show-dashboard-applications", "□", "Pendaftaran Saya", state.applications.length > 0)}
          ${item("payments", "show-dashboard-payments", "◇", "Pembayaran", hasPayments)}
          ${hasEnrollment ? item("training", "show-dashboard-training", "◉", "Pelatihan Saya") : ""}
          ${item("help", "show-dashboard-help", "?", "Bantuan")}
        </nav>
      </aside>
    `;
  }

  function closeProfileMenus(except = null) {
    document.querySelectorAll(".profile-menu").forEach((menu) => {
      if (menu === except) return;
      menu.querySelector(".profile-dropdown")?.setAttribute("hidden", "");
      menu
        .querySelector('[data-action="toggle-profile-menu"]')
        ?.setAttribute("aria-expanded", "false");
    });
  }

  function googleAuthButton() {
    const label =
      state.accountMode === "register"
        ? "Daftar dengan Google"
        : "Masuk dengan Google";
    const note = backend.googleConfigured
      ? "Google hanya membagikan identitas dasar: nama, email, dan foto profil."
      : "Isi kredensial Google di .env agar tombol ini dapat digunakan.";

    return `
      <a class="auth-google-button" href="${escapeHtml(backend.routes?.google || "#")}">
        <span class="auth-google-mark" aria-hidden="true">G</span>
        <span>${label}</span>
      </a>
      <p class="auth-provider-note">${note}</p>
      <div class="auth-divider"><span>atau gunakan email</span></div>
    `;
  }

  function navigate(target) {
    let index =
      typeof target === "number"
        ? target
        : steps.findIndex((step) => step.id === target);
    if (index < 0 || index >= steps.length) return;

    if (state.loggedIn && steps[index].id === "programs") {
      state.dashboardView = "programs";
      index = steps.findIndex((step) => step.id === "member-programs");
    }

    const publicRoutes = [
      "home",
      "about",
      "programs",
      "detail",
      "news",
      "article",
      "alumni",
      "recruiters",
      "certificate",
      "account",
    ];
    if (state.verification.pending) publicRoutes.push("verification");
    if (!state.loggedIn && !publicRoutes.includes(steps[index].id)) {
      index = steps.findIndex((step) => step.id === "account");
    }

    state.step = index;
    state.dashboardSidebarOpen = false;
    window.clearInterval(invoiceTimer);
    window.history.replaceState(null, "", `#${steps[index].id}`);
    document.querySelector(".public-nav")?.classList.remove("is-open");
    const mobileMenu = document.querySelector(".mobile-menu-button");
    mobileMenu?.setAttribute("aria-expanded", "false");
    if (mobileMenu) mobileMenu.textContent = "☰";
    render();
    window.scrollTo({ top: 0, behavior: "smooth" });
    window.setTimeout(() => app.focus(), 250);
  }

  function pageHeader(eyebrow, title, description) {
    return `
      <section class="page-heading page-shell">
        <div>
          <span class="eyebrow">${eyebrow}</span>
          <h1>${title}</h1>
          <p>${description}</p>
        </div>
        <div class="secure-note">
          <span aria-hidden="true">✓</span>
          <div>
            <strong>Pendaftaran aman</strong>
            <small>Data dilindungi dan diverifikasi</small>
          </div>
        </div>
      </section>
    `;
  }

  function programCard(program) {
    const existingApplication = state.loggedIn
      ? applicationForProgram(program)
      : null;

    return `
      <article class="program-card">
        <div class="program-card__image" style="background-position:${program.imagePosition}">
          ${program.popular ? '<span class="badge badge--orange">Populer</span>' : ""}
          <span class="program-code">${program.code}</span>
        </div>
        <div class="program-card__body">
          <div class="program-card__heading">
            <div>
              <h2>${program.title}</h2>
              <p>${program.subtitle}</p>
            </div>
            <span class="level-chip">${program.level}</span>
          </div>
          <dl class="program-facts">
            <div><dt>Durasi</dt><dd>${program.duration}</dd></div>
            <div><dt>Praktik</dt><dd>${program.practice}</dd></div>
            <div><dt>Kuota</dt><dd>${program.seats} Peserta</dd></div>
            <div><dt>Biaya</dt><dd>${money(program.price)}</dd></div>
          </dl>
          <div class="program-card__actions">
            <button class="button button--outline" data-action="select-program" data-id="${program.id}" type="button">Lihat Detail</button>
            <button class="button button--primary" data-action="quick-select-program" data-id="${program.id}" type="button">${existingApplication ? "Lihat Status" : "Pilih Program"} <span>→</span></button>
          </div>
        </div>
      </article>
    `;
  }

  function newsCard(article, compact = false) {
    return `
      <article class="academy-news-card${article.featured ? " academy-news-card--featured" : ""}${compact ? " academy-news-card--compact" : ""}">
        <button class="academy-news-card__media" data-action="open-article" data-id="${article.id}" type="button" style="--news-position:${article.imagePosition}" aria-label="Baca ${escapeHtml(article.title)}">
          <span>${escapeHtml(article.category)}</span>
        </button>
        <div class="academy-news-card__body">
          <time>${escapeHtml(article.date)}</time>
          <h3>${escapeHtml(article.title)}</h3>
          <p>${escapeHtml(article.excerpt)}</p>
          <button class="academy-arrow-link" data-action="open-article" data-id="${article.id}" type="button">Baca selengkapnya <span aria-hidden="true">&rarr;</span></button>
        </div>
      </article>
    `;
  }

  function renderHome() {
    const featuredPrograms = programs.slice(0, 3);

    return `
      <section class="company-hero">
        <div class="page-shell company-hero__inner">
          <div class="company-hero__copy">
            <span class="academy-kicker"><i></i> WELDING SCHOOL BY ${escapeHtml(branding.company)}</span>
            <h1>Where skill meets steel.<br><em>Karier dimulai di sini.</em></h1>
            <p>${escapeHtml(branding.name)} adalah ekosistem pelatihan welding yang menghubungkan pembelajaran berbasis praktik, sertifikasi, alumni, dan kebutuhan talenta industri.</p>
            <div class="company-hero__actions">
              <button class="button button--primary button--large" data-action="go-programs" type="button">Lihat Program Pelatihan <span>→</span></button>
              <button class="button company-button--light button--large" data-action="go-public-page" data-target="about" type="button">Tentang Kami</button>
            </div>
            <div class="company-hero__trust">
              <span>✓ Instruktur berpengalaman</span>
              <span>✓ Praktik berbasis industri</span>
              <span>✓ Pendampingan kompetensi</span>
            </div>
          </div>
          <aside class="company-hero__card">
            <span>PROGRAM UNGGULAN</span>
            <strong>SMAW Welder</strong>
            <p>Pelatihan intensif dari dasar keselamatan hingga persiapan uji kompetensi.</p>
            <dl>
              <div><dt>Durasi</dt><dd>80 Jam</dd></div>
              <div><dt>Praktik</dt><dd>60 Jam</dd></div>
              <div><dt>Posisi</dt><dd>1G–4G</dd></div>
            </dl>
            <button data-action="select-program" data-id="smaw" type="button">Lihat detail program <span>→</span></button>
          </aside>
        </div>
      </section>

      <section class="company-stat-strip" aria-label="Statistik ${escapeHtml(branding.name)}">
        <div class="page-shell">
          <div><strong>10+</strong><span>Tahun pengalaman industri</span></div>
          <div><strong>1.200+</strong><span>Alumni dalam jejaring</span></div>
          <div><strong>92%</strong><span>Program berbasis praktik</span></div>
          <div><strong>18+</strong><span>Mitra industri</span></div>
        </div>
      </section>

      <section class="page-shell company-about" id="about">
        <div class="company-about__visual">
          <div class="company-about__image" role="img" aria-label="Peserta mengikuti praktik pengelasan"></div>
          <div class="company-experience-card"><strong>10+</strong><span>Tahun membangun kompetensi welder</span></div>
        </div>
        <div class="company-about__copy">
          <span class="eyebrow">TENTANG ${escapeHtml(branding.company)}</span>
          <h2>Pelatihan yang menghubungkan teori, praktik, dan kebutuhan lapangan.</h2>
          <p>${escapeHtml(branding.name)} hadir untuk membantu peserta mengembangkan keterampilan pengelasan secara terstruktur. Pembelajaran dirancang melalui kombinasi materi kelas, praktik workshop, evaluasi keselamatan, dan pendampingan instruktur.</p>
          <p>Kami percaya kompetensi dibangun melalui kebiasaan kerja yang benar, pemahaman proses, serta latihan yang konsisten di lingkungan belajar yang aman.</p>
          <div class="company-values">
            <div><span>01</span><strong>Safety First</strong><small>Keselamatan menjadi dasar setiap aktivitas pelatihan.</small></div>
            <div><span>02</span><strong>Industry Ready</strong><small>Materi disusun mengikuti kebutuhan pekerjaan lapangan.</small></div>
            <div><span>03</span><strong>Continuous Growth</strong><small>Peserta didampingi untuk terus meningkatkan kompetensi.</small></div>
          </div>
          <button class="academy-arrow-link academy-arrow-link--spaced" data-action="go-public-page" data-target="about" type="button">Pelajari visi dan misi kami <span aria-hidden="true">&rarr;</span></button>
        </div>
      </section>

      <section class="company-advantages">
        <div class="page-shell">
          <div class="company-section-heading">
            <div><span class="eyebrow">MENGAPA MEMILIH KAMI</span><h2>Lingkungan belajar yang dirancang untuk hasil nyata.</h2></div>
            <p>Setiap bagian program membantu peserta memahami standar kerja sekaligus membangun keterampilan praktik.</p>
          </div>
          <div class="company-advantage-grid">
            <article><span>⚒</span><h3>Workshop Praktik</h3><p>Area praktik dan perlengkapan disiapkan untuk pembelajaran yang aman dan terarah.</p></article>
            <article><span>◎</span><h3>Instruktur Industri</h3><p>Pembelajaran didampingi praktisi yang memahami kebutuhan pekerjaan di lapangan.</p></article>
            <article><span>✓</span><h3>Evaluasi Kompetensi</h3><p>Perkembangan peserta dipantau melalui teori, praktik, logbook, dan evaluasi akhir.</p></article>
            <article><span>↗</span><h3>Arah Pengembangan Karier</h3><p>Peserta memperoleh pemahaman mengenai jalur kompetensi dan kesiapan kerja.</p></article>
          </div>
        </div>
      </section>

      <section class="page-shell company-featured-programs">
        <div class="company-section-heading">
          <div><span class="eyebrow">PROGRAM UNGGULAN</span><h2>Pilih keterampilan yang ingin Anda kuasai.</h2></div>
          <button class="text-link company-all-programs" data-action="go-programs" type="button">Lihat semua program →</button>
        </div>
        <div class="program-grid company-program-grid">
          ${featuredPrograms.map(programCard).join("")}
        </div>
      </section>

      <section class="company-process">
        <div class="page-shell">
          <div class="company-section-heading company-section-heading--light">
            <div><span class="eyebrow eyebrow--light">PROSES PELATIHAN</span><h2>Perjalanan belajar yang jelas dari awal hingga selesai.</h2></div>
          </div>
          <ol class="company-process-grid">
            <li><span>01</span><div><strong>Pilih program</strong><p>Bandingkan program sesuai tingkat pengalaman dan tujuan Anda.</p></div></li>
            <li><span>02</span><div><strong>Tentukan batch</strong><p>Pilih periode, jadwal, dan lokasi pelatihan yang tersedia.</p></div></li>
            <li><span>03</span><div><strong>Ikuti pelatihan</strong><p>Pelajari teori dasar dan praktik terarah bersama instruktur.</p></div></li>
            <li><span>04</span><div><strong>Evaluasi kompetensi</strong><p>Selesaikan penilaian dan persiapan menuju standar kompetensi.</p></div></li>
          </ol>
        </div>
      </section>

      <section class="page-shell company-facilities" id="facilities">
        <div class="company-section-heading">
          <div><span class="eyebrow">FASILITAS PELATIHAN</span><h2>Mendukung pembelajaran yang aman dan fokus.</h2></div>
        </div>
        <div class="company-facility-layout">
          <div class="company-facility-image" role="img" aria-label="Workshop pelatihan ${escapeHtml(branding.name)}"></div>
          <div class="company-facility-list">
            <div><span>✓</span><p><strong>Booth praktik individual</strong><small>Area latihan yang membantu peserta berlatih secara terarah.</small></p></div>
            <div><span>✓</span><p><strong>Perlengkapan keselamatan</strong><small>APD dan panduan keselamatan tersedia selama kegiatan praktik.</small></p></div>
            <div><span>✓</span><p><strong>Ruang teori dan materi digital</strong><small>Materi dapat dipelajari sebelum dan selama pelatihan.</small></p></div>
            <div><span>✓</span><p><strong>Logbook perkembangan</strong><small>Catatan latihan membantu instruktur memantau kemajuan peserta.</small></p></div>
          </div>
        </div>
      </section>

      <section class="page-shell company-certificate" id="certificate">
        <div>
          <span class="eyebrow eyebrow--light">VERIFIKASI SERTIFIKAT</span>
          <h2>Pastikan sertifikat pelatihan tercatat secara resmi.</h2>
          <p>Masukkan nomor sertifikat untuk memeriksa informasi penerbitan dan status dokumen peserta.</p>
        </div>
        <button class="button company-button--light button--large" data-action="go-public-page" data-target="certificate" type="button">Verifikasi Sertifikat</button>
      </section>

      <section class="page-shell academy-latest">
        <div class="company-section-heading">
          <div><span class="eyebrow">KABAR TERBARU</span><h2>Aktivitas, cerita, dan perkembangan terbaru dari akademi.</h2></div>
          <button class="text-link company-all-programs" data-action="go-public-page" data-target="news" type="button">Lihat semua berita &rarr;</button>
        </div>
        <div class="academy-latest__grid">
          ${academyNews.slice(0, 3).map((article) => newsCard(article, true)).join("")}
        </div>
      </section>

      <section class="academy-ecosystem">
        <div class="page-shell">
          <div class="academy-ecosystem__intro">
            <span class="eyebrow eyebrow--light">SATU EKOSISTEM, BANYAK PELUANG</span>
            <h2>Pelatihan selesai. Perjalanan karier terus berlanjut.</h2>
            <p>Kami menghubungkan lulusan yang siap berkembang dengan perusahaan yang mencari talenta welding terverifikasi.</p>
          </div>
          <div class="academy-ecosystem__cards">
            <article>
              <span class="academy-portal-no">01 / ALUMNI</span>
              <h3>Terus terkoneksi setelah lulus.</h3>
              <p>Perbarui profil, temukan lowongan, ikuti mentoring, dan bangun jejaring profesional.</p>
              <button data-action="go-public-page" data-target="alumni" type="button">Lihat Platform Alumni <span>&rarr;</span></button>
            </article>
            <article>
              <span class="academy-portal-no">02 / RECRUITER</span>
              <h3>Temukan talenta welding yang tepat.</h3>
              <p>Jelajahi kandidat berdasarkan proses las, posisi, pengalaman, dan status sertifikasi.</p>
              <button data-action="go-public-page" data-target="recruiters" type="button">Lihat Portal Recruiter <span>&rarr;</span></button>
            </article>
          </div>
        </div>
      </section>

      <section class="company-cta">
        <div class="page-shell">
          <div><span class="eyebrow eyebrow--light">MULAI LANGKAH ANDA</span><h2>Siap membangun kompetensi welding?</h2><p>Temukan program dan jadwal pelatihan yang sesuai dengan target Anda.</p></div>
          <button class="button button--primary button--large" data-action="go-programs" type="button">Jelajahi Program <span>→</span></button>
        </div>
      </section>
    `;
  }

  function renderAbout() {
    return `
      <section class="academy-page-hero academy-page-hero--about">
        <div class="page-shell academy-page-hero__inner">
          <span class="academy-kicker"><i></i> TENTANG KAMI</span>
          <h1>Menyiapkan kompetensi.<br><em>Membuka lebih banyak peluang.</em></h1>
          <p>Alpha Academy Welding School merupakan unit pengembangan kompetensi PT. Alpha Teknik Pratama yang berfokus pada pelatihan welding berbasis praktik, keselamatan, dan kebutuhan industri.</p>
          <div class="academy-page-hero__facts">
            <span>Sejak 2016</span><span>Cilegon, Banten</span><span>Berbasis kebutuhan industri</span>
          </div>
        </div>
      </section>

      <section class="page-shell academy-story">
        <div class="academy-story__lead">
          <span class="eyebrow">CERITA KAMI</span>
          <h2>Dibangun dari pemahaman nyata tentang dunia kerja.</h2>
        </div>
        <div class="academy-story__copy">
          <p>Berawal dari pengalaman PT. Alpha Teknik Pratama di bidang teknik dan fabrikasi, kami melihat adanya jarak antara keterampilan dasar yang dimiliki calon pekerja dengan standar yang dibutuhkan di lapangan.</p>
          <p>Alpha Academy hadir untuk menjembatani jarak tersebut. Setiap program menggabungkan teori yang relevan, jam praktik yang intensif, budaya keselamatan, serta evaluasi kompetensi yang terukur.</p>
          <blockquote>“Kami tidak hanya mengajarkan cara mengelas. Kami membangun kebiasaan kerja profesional yang akan dibawa peserta sepanjang kariernya.”</blockquote>
        </div>
      </section>

      <section class="academy-vision">
        <div class="page-shell academy-vision__grid">
          <article class="academy-vision__card academy-vision__card--primary">
            <span>VISI</span>
            <h2>Menjadi pusat pengembangan kompetensi welding yang terpercaya dan relevan bagi industri Indonesia.</h2>
          </article>
          <article class="academy-vision__card">
            <span>MISI</span>
            <ol>
              <li><b>01</b><p>Menyelenggarakan pelatihan berbasis praktik dan budaya keselamatan.</p></li>
              <li><b>02</b><p>Mengembangkan kurikulum sesuai standar dan kebutuhan dunia kerja.</p></li>
              <li><b>03</b><p>Mendampingi peserta dari proses belajar hingga pengembangan karier.</p></li>
              <li><b>04</b><p>Membangun kolaborasi berkelanjutan dengan mitra industri.</p></li>
            </ol>
          </article>
        </div>
      </section>

      <section class="page-shell academy-values-section">
        <div class="company-section-heading">
          <div><span class="eyebrow">NILAI YANG KAMI PEGANG</span><h2>Standar kerja dimulai dari budaya belajar.</h2></div>
          <p>Empat prinsip ini hadir di kelas, workshop, dan setiap interaksi dengan peserta maupun mitra.</p>
        </div>
        <div class="academy-values-grid">
          <article><span>01</span><h3>Safety</h3><p>Keselamatan adalah keputusan pertama dalam setiap proses kerja.</p></article>
          <article><span>02</span><h3>Competence</h3><p>Keterampilan dibangun dengan fondasi, praktik, evaluasi, dan konsistensi.</p></article>
          <article><span>03</span><h3>Integrity</h3><p>Data, proses, serta hasil kompetensi disampaikan secara transparan.</p></article>
          <article><span>04</span><h3>Collaboration</h3><p>Pertumbuhan lebih kuat saat akademi, alumni, dan industri bergerak bersama.</p></article>
        </div>
      </section>

      <section class="academy-milestones">
        <div class="page-shell">
          <span class="eyebrow eyebrow--light">PERJALANAN ALPHA</span>
          <div class="academy-timeline">
            <article><strong>2016</strong><p>Program pelatihan internal pertama untuk mendukung kebutuhan teknis perusahaan.</p></article>
            <article><strong>2019</strong><p>Workshop pelatihan dikembangkan untuk peserta umum dan mitra industri.</p></article>
            <article><strong>2023</strong><p>Kurikulum diperluas ke SMAW, FCAW, GTAW, GMAW, dan inspeksi dasar.</p></article>
            <article><strong>2026</strong><p>Alpha Academy menjadi ekosistem digital pelatihan, alumni, dan recruiter.</p></article>
          </div>
        </div>
      </section>

      <section class="page-shell academy-partner-band">
        <div><span class="eyebrow">KOLABORASI</span><h2>Tumbuh bersama industri.</h2></div>
        <p>Kami membuka kemitraan pelatihan khusus perusahaan, talent pipeline, penyelenggaraan uji kompetensi, dan program peningkatan keterampilan tenaga kerja.</p>
        <button class="button button--primary button--large" data-action="proposal-interest" type="button">Diskusikan Kemitraan <span>&rarr;</span></button>
      </section>
    `;
  }

  function renderNews() {
    const featured = academyNews[0];
    return `
      <section class="academy-news-hero">
        <div class="page-shell academy-news-hero__heading">
          <div><span class="academy-kicker academy-kicker--dark"><i></i> NEWSROOM</span><h1>Kabar terbaru dari<br><em>Alpha Academy.</em></h1></div>
          <p>Ikuti kegiatan pelatihan, cerita alumni, kolaborasi industri, dan agenda terbaru dari workshop kami.</p>
        </div>
        <div class="page-shell academy-featured-story">
          <button class="academy-featured-story__visual" data-action="open-article" data-id="${featured.id}" type="button" aria-label="Baca berita utama"></button>
          <div class="academy-featured-story__copy">
            <span>${featured.category} &middot; ${featured.date}</span>
            <h2>${featured.title}</h2>
            <p>${featured.excerpt}</p>
            <button class="academy-arrow-link" data-action="open-article" data-id="${featured.id}" type="button">Baca berita utama <span>&rarr;</span></button>
          </div>
        </div>
      </section>

      <section class="page-shell academy-newsroom">
        <div class="academy-newsroom__toolbar">
          <div class="academy-chip-row" aria-label="Kategori berita">
            <button class="is-active" type="button">Semua</button><button type="button">Kegiatan</button><button type="button">Alumni</button><button type="button">Industri</button><button type="button">Safety</button>
          </div>
          <label class="academy-news-search"><span aria-hidden="true">&#9906;</span><input type="search" placeholder="Cari berita..." aria-label="Cari berita"></label>
        </div>
        <div class="academy-news-grid">
          ${academyNews.slice(1).map((article) => newsCard(article)).join("")}
        </div>
        <button class="academy-load-more" data-action="load-more-news" type="button">Muat lebih banyak</button>
      </section>

      <section class="academy-newsletter">
        <div class="page-shell">
          <div><span class="eyebrow eyebrow--light">ALPHA UPDATE</span><h2>Jangan lewatkan kabar dan agenda terbaru.</h2></div>
          <form data-form="newsletter"><label><span class="sr-only">Alamat email</span><input type="email" placeholder="Alamat email Anda" required></label><button class="button button--primary" type="submit">Berlangganan</button></form>
        </div>
      </section>
    `;
  }

  function renderArticle() {
    const article = state.selectedArticle || academyNews[0];
    const related = academyNews.filter((item) => item.id !== article.id).slice(0, 3);
    return `
      <article class="academy-article">
        <header class="academy-article__header page-shell">
          <button class="academy-back-link" data-action="go-public-page" data-target="news" type="button">&larr; Kembali ke berita</button>
          <span>${escapeHtml(article.category)} &middot; ${escapeHtml(article.date)}</span>
          <h1>${escapeHtml(article.title)}</h1>
          <p>${escapeHtml(article.excerpt)}</p>
        </header>
        <div class="academy-article__image" role="img" aria-label="Dokumentasi kegiatan Alpha Academy"></div>
        <div class="academy-article__layout page-shell">
          <aside><strong>BAGIKAN</strong><button type="button">in</button><button type="button">f</button><button type="button">&#8599;</button></aside>
          <div class="academy-article__body">
            <p class="academy-article__lead">Kegiatan ini menjadi bagian dari komitmen Alpha Academy untuk menghadirkan proses belajar yang terukur, aman, dan dekat dengan kebutuhan dunia kerja.</p>
            <p>Peserta menjalani rangkaian pembelajaran yang mencakup penguatan teori dasar, demonstrasi instruktur, latihan terarah, hingga evaluasi hasil kerja. Setiap tahapan dilaksanakan dengan memperhatikan prosedur keselamatan dan standar kualitas workshop.</p>
            <h2>Kompetensi dibangun melalui proses</h2>
            <p>Tim instruktur melakukan pendampingan secara personal melalui logbook perkembangan. Catatan tersebut membantu peserta memahami area yang sudah dikuasai dan bagian yang masih perlu ditingkatkan sebelum evaluasi akhir.</p>
            <blockquote>Hasil terbaik lahir dari kombinasi disiplin, jam praktik yang cukup, dan keberanian untuk terus memperbaiki teknik.</blockquote>
            <p>Ke depan, Alpha Academy akan terus memperluas kolaborasi dengan praktisi dan mitra industri agar setiap program tetap relevan dengan dinamika kebutuhan tenaga kerja.</p>
          </div>
        </div>
      </article>
      <section class="page-shell academy-related-news">
        <div class="company-section-heading"><div><span class="eyebrow">BERITA LAINNYA</span><h2>Mungkin menarik untuk Anda.</h2></div></div>
        <div class="academy-latest__grid">${related.map((item) => newsCard(item, true)).join("")}</div>
      </section>
    `;
  }

  function renderAlumni() {
    return `
      <section class="academy-portal-hero academy-portal-hero--alumni">
        <div class="page-shell academy-portal-hero__grid">
          <div>
            <span class="academy-kicker"><i></i> ALPHA ALUMNI NETWORK</span>
            <h1>Lulus dari kelas.<br><em>Tetap tumbuh bersama.</em></h1>
            <p>Platform khusus alumni untuk membangun profil kompetensi, menemukan peluang kerja, mengikuti mentoring, dan tetap terhubung dengan jejaring Alpha Academy.</p>
            <div class="academy-portal-hero__actions"><button class="button button--primary button--large" data-action="alumni-login" type="button">Masuk sebagai Alumni</button><button class="button company-button--light button--large" data-action="proposal-interest" type="button">Aktifkan Akun</button></div>
            <div class="academy-portal-hero__proof"><strong>1.200+</strong><span>alumni dalam jejaring</span><strong>340+</strong><span>profil siap kerja</span></div>
          </div>
          <div class="academy-dashboard-mock" aria-label="Pratinjau dashboard alumni">
            <div class="academy-dashboard-mock__top"><span><i></i> ALPHA ALUMNI</span><b>AR</b></div>
            <div class="academy-dashboard-mock__welcome"><small>SELAMAT DATANG KEMBALI</small><h3>Halo, Andi!</h3><p>Profil kompetensi Anda sudah 85% lengkap.</p><div><i style="width:85%"></i></div></div>
            <div class="academy-dashboard-mock__stats"><span><b>03</b> Sertifikat</span><span><b>12</b> Lowongan cocok</span><span><b>07</b> Koneksi</span></div>
            <div class="academy-dashboard-mock__job"><small>REKOMENDASI PEKERJAAN</small><strong>Welder SMAW 3G</strong><span>PT Nusantara Fabrikasi &middot; Cilegon</span><button type="button">Lihat Lowongan &rarr;</button></div>
          </div>
        </div>
      </section>

      <section class="page-shell academy-portal-benefits">
        <div class="company-section-heading"><div><span class="eyebrow">UNTUK PERJALANAN SETELAH LULUS</span><h2>Semua yang alumni butuhkan untuk melangkah lebih jauh.</h2></div></div>
        <div class="academy-benefit-grid">
          <article><span>01</span><h3>Profil Kompetensi Digital</h3><p>Tampilkan program, sertifikat, proses las, posisi, dan pengalaman dalam satu profil profesional.</p></article>
          <article><span>02</span><h3>Lowongan Terkurasi</h3><p>Temukan peluang yang sesuai dengan kompetensi dan lokasi pilihan Anda.</p></article>
          <article><span>03</span><h3>Mentoring & Upskilling</h3><p>Ikuti sesi bersama praktisi dan dapatkan informasi program peningkatan kompetensi.</p></article>
          <article><span>04</span><h3>Jejaring Profesional</h3><p>Bangun koneksi dengan sesama alumni, instruktur, dan mitra industri.</p></article>
        </div>
      </section>

      <section class="academy-alumni-story">
        <div class="page-shell academy-alumni-story__grid">
          <div class="academy-alumni-story__portrait"><span>ALUMNI STORY &middot; 2026</span></div>
          <blockquote><span>&ldquo;</span><p>Program SMAW membentuk teknik saya, tetapi jejaring alumninya membantu saya menemukan arah karier. Dari mentoring hingga kesempatan interview, semuanya terasa lebih dekat.</p><footer><strong>Andi Ramadhan</strong><small>Alumni SMAW 3G &middot; Batch 2025</small></footer></blockquote>
        </div>
      </section>

      <section class="company-cta">
        <div class="page-shell"><div><span class="eyebrow eyebrow--light">ALUMNI ALPHA ACADEMY?</span><h2>Aktifkan profil dan temukan peluang berikutnya.</h2><p>Nomor sertifikat atau email terdaftar diperlukan untuk aktivasi awal.</p></div><button class="button button--primary button--large" data-action="alumni-login" type="button">Masuk ke Platform Alumni <span>&rarr;</span></button></div>
      </section>
    `;
  }

  function renderRecruiters() {
    return `
      <section class="academy-portal-hero academy-portal-hero--recruiter">
        <div class="page-shell academy-portal-hero__grid">
          <div>
            <span class="academy-kicker"><i></i> ALPHA TALENT CONNECT</span>
            <h1>Temukan welder.<br><em>Siap untuk kebutuhan Anda.</em></h1>
            <p>Akses kandidat alumni dengan data program, kompetensi, pengalaman, dan sertifikat yang terverifikasi oleh Alpha Academy.</p>
            <div class="academy-portal-hero__actions"><button class="button button--primary button--large" data-action="recruiter-demo" type="button">Minta Akses Recruiter</button><button class="button company-button--light button--large" data-action="proposal-interest" type="button">Jadwalkan Demo</button></div>
            <div class="academy-portal-hero__trust-row"><span>&check; Profil terkurasi</span><span>&check; Sertifikat terverifikasi</span><span>&check; Pencarian berbasis skill</span></div>
          </div>
          <div class="academy-talent-browser" aria-label="Pratinjau pencarian kandidat">
            <div class="academy-talent-browser__header"><span><i></i> TALENT DIRECTORY</span><button type="button">Filter &#9776;</button></div>
            <label><span aria-hidden="true">&#9906;</span><input type="text" value="SMAW 3G" aria-label="Contoh kata kunci kandidat" readonly></label>
            <div class="academy-talent-browser__filters"><span>Lokasi: Banten</span><span>Siap bekerja</span></div>
            <article><b>AR</b><div><strong>Andi Ramadhan <i>&check;</i></strong><small>SMAW Welder &middot; 3G</small><p>2 tahun pengalaman &middot; Cilegon</p></div><button type="button">Lihat</button></article>
            <article><b>DS</b><div><strong>Dimas Saputra <i>&check;</i></strong><small>FCAW Welder &middot; 3G</small><p>1 tahun pengalaman &middot; Serang</p></div><button type="button">Lihat</button></article>
            <article><b>RF</b><div><strong>Rizky Firmansyah <i>&check;</i></strong><small>GTAW Welder &middot; 6G</small><p>3 tahun pengalaman &middot; Bekasi</p></div><button type="button">Lihat</button></article>
            <footer>Menampilkan 3 dari 126 kandidat cocok</footer>
          </div>
        </div>
      </section>

      <section class="page-shell academy-recruiter-flow">
        <div class="company-section-heading"><div><span class="eyebrow">REKRUTMEN LEBIH TERARAH</span><h2>Dari kebutuhan posisi hingga kandidat terpilih.</h2></div><p>Dirancang sebagai titik temu yang efisien bagi tim HR, project manager, dan talent acquisition.</p></div>
        <ol><li><span>01</span><h3>Tentukan kebutuhan</h3><p>Filter proses las, posisi, pengalaman, domisili, dan kesiapan kerja.</p></li><li><span>02</span><h3>Tinjau kompetensi</h3><p>Lihat riwayat program, status sertifikat, dan profil pengalaman kandidat.</p></li><li><span>03</span><h3>Bangun shortlist</h3><p>Simpan kandidat potensial dan kolaborasikan evaluasi bersama tim.</p></li><li><span>04</span><h3>Terhubung</h3><p>Kirim minat atau jadwalkan proses rekrutmen melalui platform.</p></li></ol>
      </section>

      <section class="academy-recruiter-solutions">
        <div class="page-shell">
          <div><span class="eyebrow eyebrow--light">LEBIH DARI TALENT DIRECTORY</span><h2>Solusi pengembangan tenaga kerja untuk perusahaan.</h2></div>
          <div class="academy-solution-grid"><article><small>01</small><h3>Custom Training</h3><p>Program khusus berdasarkan proses, material, posisi, dan target kompetensi perusahaan.</p></article><article><small>02</small><h3>Talent Pool</h3><p>Akses kandidat terkurasi untuk kebutuhan proyek, kontrak, maupun posisi tetap.</p></article><article><small>03</small><h3>Competency Mapping</h3><p>Pemetaan kemampuan teknis untuk menyusun prioritas upskilling tim.</p></article></div>
        </div>
      </section>

      <section class="page-shell academy-recruiter-cta">
        <div><span class="eyebrow">BANGUN TALENT PIPELINE ANDA</span><h2>Ceritakan kebutuhan tenaga welding perusahaan Anda.</h2><p>Tim kami akan membantu menyusun akses kandidat atau program pelatihan yang paling relevan.</p></div>
        <button class="button button--primary button--large" data-action="recruiter-demo" type="button">Hubungi Tim Kemitraan <span>&rarr;</span></button>
      </section>
    `;
  }

  function renderCertificate() {
    return `
      <section class="academy-verify-hero">
        <div class="page-shell">
          <span class="academy-kicker"><i></i> CERTIFICATE VERIFICATION</span>
          <h1>Verifikasi cepat.<br><em>Kepercayaan lebih kuat.</em></h1>
          <p>Periksa keaslian sertifikat yang diterbitkan oleh Alpha Academy Welding School melalui nomor unik dokumen.</p>
        </div>
      </section>

      <section class="page-shell academy-verify-layout">
        <div class="academy-verify-card">
          <div class="academy-verify-card__heading"><span class="academy-verify-icon">&check;</span><div><span class="eyebrow">VERIFIKASI DOKUMEN</span><h2>Masukkan nomor sertifikat</h2><p>Nomor terdiri dari kode program, tahun, dan nomor peserta.</p></div></div>
          <form data-form="certificate-verification">
            <label for="certificate-number">Nomor sertifikat</label>
            <div><input id="certificate-number" name="certificate_number" type="text" placeholder="Contoh: AA-SMAW-2026-00128" autocomplete="off" required><button class="button button--primary" type="submit">Periksa Sertifikat</button></div>
            <small>Data contoh untuk prototype: <button data-action="fill-certificate" type="button">AA-SMAW-2026-00128</button></small>
          </form>
          <div class="academy-certificate-result" id="certificate-result" hidden>
            <div class="academy-certificate-result__status"><span>&check;</span><div><strong>Sertifikat Valid</strong><small>Dokumen diterbitkan oleh Alpha Academy Welding School</small></div></div>
            <dl><div><dt>Nama peserta</dt><dd>Andi Ramadhan</dd></div><div><dt>Program</dt><dd>SMAW Welder</dd></div><div><dt>Posisi</dt><dd>3G Plate</dd></div><div><dt>Nomor sertifikat</dt><dd>AA-SMAW-2026-00128</dd></div><div><dt>Tanggal terbit</dt><dd>30 Juli 2026</dd></div><div><dt>Status</dt><dd><span>Aktif</span></dd></div></dl>
          </div>
        </div>
        <aside class="academy-verify-help">
          <span class="eyebrow">PANDUAN</span><h2>Di mana nomor sertifikat berada?</h2>
          <div class="academy-certificate-sample"><span>ALPHA ACADEMY</span><b>CERTIFICATE</b><i></i><small>Certificate No.</small><strong>AA-SMAW-2026-XXXXX</strong></div>
          <ol><li><span>1</span><p>Lihat bagian bawah sertifikat fisik atau digital.</p></li><li><span>2</span><p>Masukkan nomor lengkap termasuk tanda hubung.</p></li><li><span>3</span><p>Hubungi kami jika data tidak ditemukan atau berbeda.</p></li></ol>
          <button class="academy-arrow-link" data-action="proposal-interest" type="button">Butuh bantuan verifikasi? <span>&rarr;</span></button>
        </aside>
      </section>

      <section class="academy-verification-trust"><div class="page-shell"><article><span>&check;</span><div><strong>Data terverifikasi</strong><p>Sumber informasi berasal dari basis data akademi.</p></div></article><article><span>&#9635;</span><div><strong>Nomor dokumen unik</strong><p>Setiap sertifikat memiliki identitas penerbitan tersendiri.</p></div></article><article><span>&#128274;</span><div><strong>Akses aman</strong><p>Informasi pribadi sensitif tidak ditampilkan ke publik.</p></div></article></div></section>
    `;
  }

  function renderPrograms() {
    const filtered = programs.filter((program) => {
      const matchesSearch =
        `${program.title} ${program.subtitle} ${program.code}`
          .toLowerCase()
          .includes(state.search.toLowerCase());
      const matchesLevel =
        state.level === "Semua Level" || program.level === state.level;
      return matchesSearch && matchesLevel;
    });

    return `
      <section class="program-hero">
        <div class="page-shell program-hero__inner">
          <div>
            <span class="eyebrow eyebrow--light">PROGRAM PELATIHAN WELDER</span>
            <h1>Mulai perjalanan menjadi<br><em>welder profesional.</em></h1>
            <p>Pilih program sesuai tujuan karier, tentukan jadwal, lalu selesaikan pendaftaran secara online.</p>
            <div class="hero-points">
              <span>✓ Instruktur industri</span>
              <span>✓ Workshop lengkap</span>
              <span>✓ Sertifikasi resmi</span>
            </div>
          </div>
        </div>
      </section>
      <section class="page-shell program-catalog">
        <div class="catalog-toolbar">
          <label class="search-box">
            <span aria-hidden="true">⌕</span>
            <input id="program-search" type="search" value="${escapeHtml(state.search)}" placeholder="Cari nama atau proses pelatihan..." aria-label="Cari program">
          </label>
          <label class="select-box">
            <span>LEVEL</span>
            <select id="level-filter" aria-label="Filter level">
              ${["Semua Level", "Pemula", "Menengah", "Lanjutan"]
                .map(
                  (level) =>
                    `<option ${state.level === level ? "selected" : ""}>${level}</option>`,
                )
                .join("")}
            </select>
          </label>
        </div>
        <div class="catalog-result">
          <div>
            <span class="eyebrow">PROGRAM TERSEDIA</span>
            <h2>Temukan program yang tepat untuk Anda</h2>
          </div>
          <span>Menampilkan <strong>${filtered.length}</strong> program</span>
        </div>
        ${
          filtered.length
            ? `<div class="program-grid">${filtered.map(programCard).join("")}</div>`
            : `<div class="empty-state"><span>⌕</span><h2>Program tidak ditemukan</h2><p>Coba kata kunci atau level yang berbeda.</p></div>`
        }
      </section>
    `;
  }

  function renderDetailPage() {
    const program = state.selectedProgram;
    return `
      ${pageHeader(
        "LANGKAH 1 DARI 4 · DETAIL PROGRAM",
        program.title,
        "Pelajari materi, jadwal, fasilitas, dan biaya sebelum memilih batch.",
      )}
      <section class="page-shell detail-layout">
        <article class="detail-main">
          <div class="detail-cover" style="background-position:${program.imagePosition}">
            <span class="badge badge--orange">${program.code}</span>
            <div>
              <small>${program.level}</small>
              <h2>${program.subtitle}</h2>
            </div>
          </div>
          <div class="detail-section">
            <h2>Tentang program</h2>
            <p>${program.description}</p>
            <div class="feature-cards">
              <div><span>◷</span><strong>${program.duration}</strong><small>Total pelatihan</small></div>
              <div><span>▤</span><strong>${program.theory}</strong><small>Materi teori</small></div>
              <div><span>⚒</span><strong>${program.practice}</strong><small>Praktik workshop</small></div>
              <div><span>◉</span><strong>${program.seats}</strong><small>Peserta per kelas</small></div>
            </div>
          </div>
          <div class="detail-columns">
            <div>
              <h3>Materi yang dipelajari</h3>
              <ul class="check-list">
                <li>Prinsip dasar proses ${program.code}</li>
                <li>Keselamatan dan kesehatan kerja pengelasan</li>
                <li>Persiapan material dan mesin las</li>
                <li>Teknik posisi pengelasan</li>
                <li>Inspeksi visual dan perbaikan cacat</li>
                <li>Persiapan uji kompetensi</li>
              </ul>
            </div>
            <div>
              <h3>Fasilitas pelatihan</h3>
              <ul class="check-list">
                <li>Ruang kelas ber-AC</li>
                <li>Booth las individual</li>
                <li>APD dan consumable praktik</li>
                <li>Modul digital dan logbook</li>
                <li>Sertifikat pelatihan</li>
              </ul>
            </div>
          </div>
          <div class="position-strip">
            <span>POSISI PENGELASAN</span>
            <div>${program.positions.map((position) => `<strong>${position}</strong>`).join("")}</div>
          </div>
        </article>
        <aside class="booking-card">
          <span class="booking-card__label">BIAYA PROGRAM</span>
          <strong class="booking-card__price">${money(program.price)}</strong>
          <small>Termasuk materi, praktik, APD, dan sertifikat.</small>
          <div class="booking-card__divider"></div>
          <div class="booking-fact"><span>Jadwal terdekat</span><strong>10 Agustus 2026</strong></div>
          <div class="booking-fact"><span>Kuota tersedia</span><strong class="text-green">5 kursi</strong></div>
          <div class="booking-fact"><span>Lokasi</span><strong>Workshop Cilegon</strong></div>
          <button class="button button--primary button--large" data-action="select-detail-program" type="button">Pilih Program &amp; Jadwal <span>→</span></button>
          <button class="text-button" data-action="back-program-list" type="button">← Kembali ke daftar program</button>
        </aside>
      </section>
    `;
  }

  function renderDetail() {
    if (!state.loggedIn) return renderDetailPage();

    return renderProgramDashboardPage(
      "programs",
      "Detail Program",
      state.selectedProgram.title,
      "Pelajari materi, fasilitas, durasi, dan biaya sebelum memilih jadwal batch.",
      `<div class="dashboard-embedded-program-page">${renderDetailPage()}</div>`,
    );
  }

  function renderBatchPage() {
    const selected = state.selectedBatch;
    return `
      ${pageHeader(
        "LANGKAH 2 DARI 4 · PILIH BATCH",
        "Pilih jadwal yang paling sesuai",
        `Program ${state.selectedProgram.title} · ${state.selectedProgram.duration}`,
      )}
      <section class="page-shell selection-layout">
        <div class="selection-main">
          <div class="notice notice--blue">
            <span>i</span>
            <div><strong>Kursi belum dipesan pada tahap ini</strong><p>Reservasi kursi dimulai setelah invoice dibuat dan berlaku selama 24 jam.</p></div>
          </div>
          <div class="batch-list">
            ${batches
              .map(
                (batch) => `
                  <button class="batch-card ${selected.id === batch.id ? "is-selected" : ""}" data-action="select-batch" data-id="${batch.id}" type="button">
                    <span class="radio-mark"></span>
                    <div class="batch-date"><strong>${batch.start.split(" ")[0]}</strong><span>${batch.start.split(" ")[1].slice(0, 3).toUpperCase()}</span><small>2026</small></div>
                    <div class="batch-card__content">
                      <div>
                        <h2>${batch.label}</h2>
                        ${batch.recommended ? '<span class="badge badge--green">Direkomendasikan</span>' : ""}
                      </div>
                      <dl>
                        <div><dt>Periode</dt><dd>${batch.start} – ${batch.end}</dd></div>
                        <div><dt>Jadwal</dt><dd>${batch.schedule}</dd></div>
                        <div><dt>Lokasi</dt><dd>${batch.location}</dd></div>
                      </dl>
                    </div>
                    <div class="seat-info"><span>Sisa kuota</span><strong>${batch.seatsLeft} kursi</strong></div>
                  </button>
                `,
              )
              .join("")}
          </div>
        </div>
        <aside class="summary-card">
          <span class="eyebrow">PILIHAN ANDA</span>
          <div class="summary-program">
            <span class="summary-program__thumb"></span>
            <div><strong>${state.selectedProgram.title}</strong><small>${state.selectedProgram.subtitle}</small></div>
          </div>
          <dl class="summary-list">
            <div><dt>Batch</dt><dd>${selected.label}</dd></div>
            <div><dt>Tanggal</dt><dd>${selected.start}</dd></div>
            <div><dt>Jadwal</dt><dd>${selected.schedule}</dd></div>
            <div><dt>Lokasi</dt><dd>${selected.location}</dd></div>
          </dl>
          <div class="summary-total"><span>Total biaya</span><strong>${money(state.selectedProgram.price)}</strong></div>
          <button class="button button--primary button--large" data-action="next" type="button">Daftar Sekarang <span>→</span></button>
          <button class="text-button" data-action="back" type="button">← Kembali ke detail</button>
        </aside>
      </section>
    `;
  }

  function renderBatch() {
    if (!state.loggedIn) return renderBatchPage();

    return renderProgramDashboardPage(
      "application",
      "Pemilihan Batch",
      "Pilih jadwal yang paling sesuai",
      `Program ${state.selectedProgram.title} · ${state.selectedProgram.duration}`,
      `<div class="dashboard-embedded-program-page">${renderBatchPage()}</div>`,
    );
  }

  function accountForm() {
    if (state.accountMode === "login") {
      return `
        <form class="auth-form" data-form="account">
          ${googleAuthButton()}
          <label class="field field--full"><span>Email atau username</span><input name="login" type="text" value="" placeholder="nama@email.com atau username" autocomplete="username" required></label>
          <label class="field field--full"><span>Password</span><input name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required></label>
          <div class="auth-options"><label><input name="remember" type="checkbox"> Ingat saya</label><button class="text-link" type="button">Lupa password?</button></div>
          <button class="button button--primary button--large button--full" type="submit" data-submit-label="Masuk ke Dashboard">Masuk ke Dashboard <span>→</span></button>
        </form>
      `;
    }
    return `
      <form class="auth-form" data-form="account">
        ${googleAuthButton()}
        <div class="field-grid">
          <label class="field field--full"><span>Username</span><input name="username" value="${escapeHtml(state.registration.username)}" placeholder="contoh: budi.welder" minlength="3" maxlength="30" pattern="[A-Za-z0-9._-]+" autocomplete="username" required></label>
          <label class="field field--full"><span>Email</span><input name="email" type="email" value="${escapeHtml(state.registration.email)}" placeholder="nama@email.com" autocomplete="email" required></label>
          <label class="field field--full"><span>Buat password</span><input name="password" type="password" placeholder="Minimal 8 karakter, huruf dan angka" minlength="8" autocomplete="new-password" required></label>
          <label class="field field--full"><span>Ulangi password</span><input name="password_confirmation" type="password" placeholder="Ketik ulang password" minlength="8" autocomplete="new-password" required></label>
        </div>
        <p class="auth-email-note">Username dapat digunakan untuk login dan tidak dibuat otomatis dari email. Data diri dilengkapi setelah akun aktif.</p>
        <label class="agreement"><input name="agreement" type="checkbox" checked required><span>Saya menyetujui <a href="#terms">Syarat &amp; Ketentuan</a> serta <a href="#privacy">Kebijakan Privasi</a>.</span></label>
        <button class="button button--primary button--large button--full" type="submit" data-submit-label="Buat Akun &amp; Masuk">Buat Akun &amp; Masuk <span>→</span></button>
      </form>
    `;
  }

  function renderAccount() {
    return `
      <section class="auth-page">
        <div class="auth-visual">
          <div class="auth-visual__overlay">
            <span class="eyebrow eyebrow--light">LANGKAH SELANJUTNYA</span>
            <h1>Satu akun untuk seluruh perjalanan pelatihan.</h1>
            <p>Pantau pendaftaran, pembayaran, jadwal, materi, nilai, hingga sertifikat dari satu tempat.</p>
            <div class="auth-trust">
              <div><span>✓</span><strong>Data terlindungi</strong></div>
              <div><span>✓</span><strong>Proses transparan</strong></div>
              <div><span>✓</span><strong>Akses selamanya</strong></div>
            </div>
          </div>
        </div>
        <div class="auth-panel">
          <div class="auth-panel__inner">
            <span class="eyebrow">PORTAL CALON PESERTA</span>
            <h1>${state.accountMode === "register" ? "Buat akun baru" : "Selamat datang kembali"}</h1>
            <p>${state.accountMode === "register" ? "Daftar menggunakan email untuk melihat dan memilih program pelatihan." : "Masuk menggunakan email untuk membuka dashboard Anda."}</p>
            <div class="auth-tabs" role="tablist">
              <button class="${state.accountMode === "register" ? "is-active" : ""}" data-action="account-mode" data-mode="register" type="button">Saya belum punya akun</button>
              <button class="${state.accountMode === "login" ? "is-active" : ""}" data-action="account-mode" data-mode="login" type="button">Saya sudah punya akun</button>
            </div>
            ${accountForm()}
            <div class="secure-caption"><span>◇</span> Informasi Anda disimpan dengan aman dan hanya digunakan untuk proses pelatihan.</div>
          </div>
        </div>
      </section>
    `;
  }

  function renderVerification() {
    const email = state.verification.email || state.registration.email;
    const localCode = state.verification.debugCode
      ? `
        <div class="verification-debug">
          <span>MODE TESTING LOKAL</span>
          <strong>${escapeHtml(state.verification.debugCode)}</strong>
          <small>Kode ini hanya ditampilkan ketika APP_ENV=local.</small>
        </div>
      `
      : "";

    return `
      <section class="auth-page verification-page">
        <div class="auth-visual verification-visual">
          <div class="auth-visual__overlay">
            <span class="eyebrow eyebrow--light">KEAMANAN AKUN</span>
            <h1>Pastikan email benar-benar milik Anda.</h1>
            <p>Kode verifikasi membantu melindungi data pendaftaran dan akses dashboard peserta.</p>
            <div class="auth-trust">
              <div><span>✓</span><strong>Berlaku 10 menit</strong></div>
              <div><span>✓</span><strong>Maksimal 5 percobaan</strong></div>
              <div><span>✓</span><strong>Kode sekali pakai</strong></div>
            </div>
          </div>
        </div>
        <div class="auth-panel">
          <div class="auth-panel__inner verification-panel">
            <span class="verification-icon" aria-hidden="true">✉</span>
            <span class="eyebrow">VERIFIKASI EMAIL</span>
            <h1>Masukkan kode 6 digit</h1>
            <p>Kode telah dikirim ke <strong>${escapeHtml(email)}</strong>. Periksa inbox atau folder spam.</p>
            ${localCode}
            <form class="auth-form verification-form" data-form="verification">
              <label class="field field--full">
                <span>Kode verifikasi</span>
                <input
                  id="verification-code"
                  class="verification-code-input"
                  name="code"
                  type="text"
                  inputmode="numeric"
                  autocomplete="one-time-code"
                  pattern="[0-9]{6}"
                  maxlength="6"
                  placeholder="000000"
                  aria-describedby="verification-help"
                  required
                >
              </label>
              <small id="verification-help">Kode hanya dapat digunakan satu kali.</small>
              <button class="button button--primary button--large button--full" type="submit" data-submit-label="Verifikasi Email">
                Verifikasi Email <span>→</span>
              </button>
            </form>
            <div class="verification-actions">
              <span>Belum menerima kode?</span>
              <button class="text-link" data-action="resend-verification" type="button">Kirim ulang kode</button>
            </div>
            <button class="verification-back" data-action="back-to-account" type="button">← Kembali ke halaman login</button>
          </div>
        </div>
      </section>
    `;
  }

  function renderMemberPrograms() {
    const isApproved = state.applicationStatus === "approved";
    const isRejected = state.applicationStatus === "rejected";
    const isPaid = state.invoice?.status === "paid";
    const showingPrograms = state.dashboardView === "programs";
    const showingApplications = state.dashboardView === "applications";
    const showingApplicationDetail =
      state.dashboardView === "application-detail";
    const showingPayments = state.dashboardView === "payments";
    const showingTraining = state.dashboardView === "training";
    const showingTrainingDetail = state.dashboardView === "training-detail";
    const showingHelp = state.dashboardView === "help";
    const showingProfile = state.dashboardView === "profile";
    const sidebarSection = showingApplicationDetail
      ? "applications"
      : showingTrainingDetail
        ? "training"
        : state.dashboardView;
    const activeEnrollments = state.applications.filter(
      (application) => application.enrollment?.status === "active",
    );
    const paymentApplications = state.applications.filter(
      (application) => application.invoice,
    );
    const filtered = programs.filter((program) => {
      const matchesSearch =
        `${program.title} ${program.subtitle} ${program.code}`
          .toLowerCase()
          .includes(state.search.toLowerCase());
      const matchesLevel =
        state.level === "Semua Level" || program.level === state.level;
      return matchesSearch && matchesLevel;
    });

    const programSelection = `
      <section id="program-list" class="member-program-section">
        <div class="member-program-heading">
          <div>
            <span class="eyebrow">PROGRAM PELATIHAN</span>
            <h2>Pilih program untuk memulai pendaftaran</h2>
            <p>Anda bisa membaca detail terlebih dahulu atau langsung memilih jadwal batch.</p>
          </div>
          <div class="member-program-filters">
            <label class="search-box">
              <span aria-hidden="true">⌕</span>
              <input id="program-search" type="search" value="${escapeHtml(state.search)}" placeholder="Cari program..." aria-label="Cari program">
            </label>
            <label class="select-box">
              <span>LEVEL</span>
              <select id="level-filter" aria-label="Filter level">
                ${["Semua Level", "Pemula", "Menengah", "Lanjutan"]
                  .map(
                    (level) =>
                      `<option ${state.level === level ? "selected" : ""}>${level}</option>`,
                  )
                  .join("")}
              </select>
            </label>
          </div>
        </div>
        ${
          filtered.length
            ? `<div class="program-grid member-program-grid">${filtered.map(programCard).join("")}</div>`
            : `<div class="empty-state"><span>⌕</span><h2>Program tidak ditemukan</h2><p>Coba kata kunci atau level yang berbeda.</p></div>`
        }
      </section>
    `;

    const profileData = state.registration;
    const profileContent = `
      <section class="participant-profile-page">
        <div class="participant-profile-page__status ${profileData.profileComplete ? "is-complete" : "is-incomplete"}">
          <span aria-hidden="true">${profileData.profileComplete ? "✓" : "!"}</span>
          <div>
            <strong>${profileData.profileComplete ? "Data diri sudah lengkap" : "Lengkapi data diri untuk mendaftar"}</strong>
            <p>${profileData.profileComplete ? "Data berikut akan otomatis digunakan pada formulir pendaftaran pelatihan." : "Profil wajib dilengkapi sebelum Anda melanjutkan ke tahap unggah dokumen."}</p>
          </div>
        </div>
        <form class="form-card participant-profile-form" data-form="profile">
          <div class="form-section">
            <div class="form-section__title"><span>01</span><div><h2>Informasi akun</h2><p>Username dapat digunakan bersama email untuk masuk ke akun.</p></div></div>
            <div class="field-grid">
              ${inputField("Username", "username", profileData.username, { full: true })}
              ${inputField("Nama lengkap", "fullName", profileData.fullName, { full: true })}
              <label class="field field--full"><span>Email</span><input type="email" value="${escapeHtml(profileData.email)}" readonly aria-readonly="true"></label>
              ${inputField("Nomor HP / WhatsApp", "phone", profileData.phone)}
            </div>
          </div>
          <div class="form-section">
            <div class="form-section__title"><span>02</span><div><h2>Identitas peserta</h2><p>Isi sesuai dokumen identitas yang akan diunggah saat pendaftaran.</p></div></div>
            <div class="field-grid">
              <label class="field">
                <span>Jenis identitas *</span>
                <select name="identityType" required>
                  <option value="">Pilih identitas</option>
                  <option value="ktp" ${profileData.identityType === "ktp" ? "selected" : ""}>KTP</option>
                  <option value="passport" ${profileData.identityType === "passport" ? "selected" : ""}>Paspor</option>
                </select>
              </label>
              ${inputField("Nomor identitas", "identityNumber", profileData.identityNumber)}
              ${inputField("Tempat lahir", "birthPlace", profileData.birthPlace)}
              ${inputField("Tanggal lahir", "birthDate", profileData.birthDate, { type: "date" })}
              <label class="field">
                <span>Jenis kelamin *</span>
                <select name="gender" required>
                  <option value="">Pilih jenis kelamin</option>
                  <option value="male" ${profileData.gender === "male" ? "selected" : ""}>Laki-laki</option>
                  <option value="female" ${profileData.gender === "female" ? "selected" : ""}>Perempuan</option>
                </select>
              </label>
            </div>
          </div>
          <div class="form-section">
            <div class="form-section__title"><span>03</span><div><h2>Alamat dan latar belakang</h2><p>Data domisili dan pendidikan akan otomatis masuk ke formulir pendaftaran.</p></div></div>
            <div class="field-grid">
              ${inputField("Alamat lengkap", "address", profileData.address, { full: true })}
              ${inputField("Kota / Kabupaten", "city", profileData.city)}
              ${inputField("Provinsi", "province", profileData.province)}
              ${inputField("Kode pos", "postalCode", profileData.postalCode, { required: false })}
              ${selectField("Pendidikan terakhir", "education", profileData.education, ["", "SMP", "SMA/SMK", "Diploma", "Sarjana"])}
              ${inputField("Pekerjaan", "occupation", profileData.occupation, { required: false })}
            </div>
          </div>
          <div class="form-section">
            <div class="form-section__title"><span>04</span><div><h2>Kontak darurat</h2><p>Kontak ini hanya digunakan ketika diperlukan selama kegiatan pelatihan.</p></div></div>
            <div class="field-grid">
              ${inputField("Nama kontak darurat", "emergencyName", profileData.emergencyName)}
              ${inputField("Nomor kontak darurat", "emergencyPhone", profileData.emergencyPhone)}
            </div>
          </div>
          <div class="form-actions participant-profile-form__actions">
            <button class="button button--primary button--large" type="submit" data-submit-label="Simpan Profil &amp; Data Diri">Simpan Profil &amp; Data Diri <span>→</span></button>
          </div>
        </form>
      </section>
    `;

    const applicationStatusLabels = {
      submitted: "Menunggu review",
      under_review: "Sedang diperiksa",
      approved: "Disetujui",
      rejected: "Ditolak",
    };
    const invoiceStatusLabels = {
      unpaid: "Menunggu pembayaran",
      paid: "Lunas",
      expired: "Kedaluwarsa",
      cancelled: "Dibatalkan",
      refunded: "Dikembalikan",
    };
    const recordCard = (application, section) => {
      const program =
        programs.find(
          (item) =>
            Number(item.databaseId) ===
            Number(application.training_program_id),
        ) || state.selectedProgram;
      const programBatch = programBatches(program).find(
        (batch) =>
          Number(batch.databaseId) ===
          Number(application.training_batch_id),
      );
      const invoice = application.invoice;
      const enrollment = application.enrollment;
      const status =
        section === "payments"
          ? invoiceStatusLabels[invoice?.status] || "Invoice belum dibuat"
          : section === "training"
            ? enrollment?.status === "active"
              ? "Pelatihan aktif"
              : "Belum aktif"
            : applicationStatusLabels[application.status] || application.status;
      const action =
        section === "payments"
          ? "open-payment-record"
          : section === "training"
            ? "open-training-record"
            : "open-application-record";
      const actionLabel =
        section === "payments"
          ? invoice
            ? "Lihat Pembayaran"
            : "Buat Invoice"
          : section === "training"
            ? "Lihat Pelatihan"
            : "Lihat Status";

      return `
        <article class="participant-program-record ${Number(state.application?.id) === Number(application.id) ? "is-selected" : ""}">
          <div class="participant-program-record__heading">
            <span class="program-code">${escapeHtml(program.code)}</span>
            <span class="badge ${invoice?.status === "paid" || enrollment?.status === "active" ? "badge--green" : "badge--orange-soft"}">${escapeHtml(status)}</span>
          </div>
          <h3>${escapeHtml(program.title)}</h3>
          <p>${escapeHtml(programBatch?.label || application.batch?.name || "Batch belum ditentukan")}</p>
          <dl>
            <div><dt>No. Pendaftaran</dt><dd>${escapeHtml(application.registration_number)}</dd></div>
            ${invoice ? `<div><dt>Invoice</dt><dd>${escapeHtml(invoice.invoice_number)}</dd></div>` : ""}
          </dl>
          <button class="button button--outline button--full" data-action="${action}" data-application-id="${application.id}" type="button">${actionLabel}</button>
        </article>
      `;
    };
    const recordsPanel = (section, records, title, description) => `
      <section class="participant-records-panel">
        <div class="participant-records-panel__heading">
          <div><span class="eyebrow">${section === "training" ? "PELATIHAN SAYA" : section === "payments" ? "RIWAYAT PEMBAYARAN" : "PENDAFTARAN SAYA"}</span><h2>${title}</h2><p>${description}</p></div>
          <button class="button button--primary" data-action="show-dashboard-programs" type="button">Tambah Program <span>→</span></button>
        </div>
        ${
          records.length
            ? `<div class="participant-program-records">${records.map((application) => recordCard(application, section)).join("")}</div>`
            : `<div class="empty-state"><span>i</span><h2>Belum ada data</h2><p>Pilih program pelatihan untuk memulai proses baru.</p></div>`
        }
      </section>
    `;

    const applicationPanel = `
      <section class="application-status-card ${isApproved ? "is-approved" : isRejected ? "is-rejected" : "is-pending"}">
        <div class="application-status-card__heading">
          <span class="application-status-icon">${isApproved ? "✓" : isRejected ? "!" : "◷"}</span>
          <div>
            <span class="eyebrow">${isApproved ? "PENDAFTARAN DISETUJUI" : isRejected ? "PENDAFTARAN PERLU DIPERBAIKI" : "MENUNGGU PERSETUJUAN ADMIN"}</span>
            <h2>${isApproved ? "Pendaftaran Anda telah disetujui" : isRejected ? "Pendaftaran belum dapat disetujui" : "Pendaftaran Anda sedang diperiksa"}</h2>
            <p>${
              isApproved
                ? state.invoice
                  ? isPaid
                    ? `Invoice ${escapeHtml(state.invoice.invoice_number)} sudah lunas dan pelatihan Anda telah aktif.`
                    : `Invoice ${escapeHtml(state.invoice.invoice_number)} sudah dibuat dan menunggu pembayaran.`
                  : "Silakan periksa ringkasan biaya dan buat invoice untuk mengamankan kursi."
                : isRejected
                  ? escapeHtml(state.application?.verification_notes || "Silakan hubungi admin untuk mengetahui data yang perlu diperbaiki.")
                : "Tim admin sedang memeriksa data peserta dan dokumen yang Anda kirim. Status terbaru akan tampil di dashboard ini."
            }</p>
          </div>
          <span class="badge ${isApproved ? "badge--green" : isRejected ? "badge--red-soft" : "badge--orange-soft"}">${isApproved ? "Disetujui" : isRejected ? "Ditolak" : "Dalam Review"}</span>
        </div>
        <div class="application-overview">
          <div><span>Program</span><strong>${state.selectedProgram.title}</strong><small>${state.selectedProgram.subtitle}</small></div>
          <div><span>Batch</span><strong>${state.selectedBatch.label}</strong><small>${state.selectedBatch.start}</small></div>
          <div><span>Lokasi</span><strong>Workshop Cilegon</strong><small>${state.selectedBatch.location}</small></div>
          <div><span>Nomor Pendaftaran</span><strong>${escapeHtml(state.application?.registration_number || "Diproses")}</strong><small>${state.application?.submitted_at ? new Date(state.application.submitted_at).toLocaleDateString("id-ID") : "Baru dikirim"}</small></div>
        </div>
        <ol class="application-progress-dashboard" aria-label="Status pendaftaran">
          <li class="is-done"><span>✓</span><div><strong>Akun dibuat</strong><small>Email terdaftar</small></div></li>
          <li class="is-done"><span>✓</span><div><strong>Data &amp; dokumen</strong><small>Sudah dikirim</small></div></li>
          <li class="${isApproved ? "is-done" : "is-current"}"><span>${isApproved ? "✓" : "3"}</span><div><strong>Persetujuan admin</strong><small>${isApproved ? "Disetujui" : "Sedang diperiksa"}</small></div></li>
          <li class="${isPaid ? "is-done" : isApproved ? "is-current" : ""}"><span>${isPaid ? "✓" : "4"}</span><div><strong>Pembayaran</strong><small>${isPaid ? "Lunas" : state.invoice ? "Invoice sudah dibuat" : isApproved ? "Siap dilanjutkan" : "Menunggu persetujuan"}</small></div></li>
        </ol>
        <div class="application-status-actions">
          ${
            isApproved
              ? `<button class="button button--primary" data-action="continue-approved" type="button">${state.invoice ? (isPaid ? "Lihat Pembayaran" : "Lihat Invoice") : "Lihat Rincian Biaya"} <span>→</span></button>`
              : isRejected
                ? '<button class="button button--outline" data-action="show-dashboard-programs" type="button">Lihat Program Lain</button>'
                : '<button class="button button--outline" data-action="refresh-application" type="button">↻ Muat Status Terbaru</button>'
          }
          <p>Notifikasi perubahan status juga akan dikirim ke <strong>${escapeHtml(state.registration.email)}</strong>.</p>
        </div>
      </section>
    `;
    const applicationDetailPanel = `
      <section class="participant-detail-toolbar">
        <button class="participant-detail-back" data-action="back-to-applications" type="button">
          <span aria-hidden="true">←</span> Kembali ke daftar pendaftaran
        </button>
        <div>
          <span class="eyebrow">DETAIL PENDAFTARAN</span>
          <strong>${escapeHtml(state.selectedProgram.title)}</strong>
        </div>
      </section>
      ${applicationPanel}
    `;

    const dashboardStartPanel = `
      <section class="dashboard-start-card">
        <div>
          <span class="eyebrow">MULAI PENDAFTARAN</span>
          <h2>Temukan program yang sesuai dengan target karier Anda</h2>
          <p>Pilih program dari halaman Program Pelatihan di dalam dashboard, tentukan batch, lalu lengkapi data peserta.</p>
          <button class="button button--primary" data-action="show-dashboard-programs" type="button">Buka Program Pelatihan <span>→</span></button>
        </div>
        <ol>
          <li><span>1</span><div><strong>Pilih program</strong><small>Bandingkan materi, durasi, dan biaya.</small></div></li>
          <li><span>2</span><div><strong>Tentukan batch</strong><small>Pilih jadwal dan lokasi pelatihan.</small></div></li>
          <li><span>3</span><div><strong>Kirim pendaftaran</strong><small>Pantau persetujuan melalui dashboard.</small></div></li>
        </ol>
      </section>
    `;

    const homeContent = `
      <div class="dashboard-metrics applicant-metrics">
        <article><span class="metric-icon metric-icon--green">${activeEnrollments.length}</span><div><small>Pelatihan Aktif</small><strong>${activeEnrollments.length}</strong><span>Program sudah lunas</span></div></article>
        <article><span class="metric-icon metric-icon--orange">▤</span><div><small>Pendaftaran</small><strong>${state.applications.length}</strong><span>Semua program Anda</span></div></article>
        <article><span class="metric-icon metric-icon--blue">◇</span><div><small>Invoice</small><strong>${paymentApplications.length}</strong><span>${paymentApplications.filter((application) => application.invoice?.status === "unpaid").length} menunggu pembayaran</span></div></article>
        <article><span class="metric-icon metric-icon--slate">+</span><div><small>Program Tersedia</small><strong>${programs.length}</strong><span>Anda boleh mengambil program berbeda</span></div></article>
      </div>
      ${
        state.applications.length
          ? recordsPanel(
              "applications",
              state.applications,
              "Program dan pendaftaran Anda",
              "Setiap program memiliki status pendaftaran, pembayaran, dan pelatihan sendiri.",
            )
          : dashboardStartPanel
      }
    `;
    const trainingSections = [
      ["overview", "Ringkasan"],
      ["materials", "Materi"],
      ["schedule", "Jadwal"],
      ["attendance", "Kehadiran"],
      ["logbook", "Logbook"],
      ["competency", "Kompetensi"],
      ["certificate", "Sertifikat"],
    ];
    const trainingSectionLabels = Object.fromEntries(trainingSections);
    const trainingAccessPanel =
      showingTrainingDetail && state.enrollment?.status === "active"
        ? `
          <section class="participant-detail-toolbar">
            <button class="participant-detail-back" data-action="back-to-training" type="button">
              <span aria-hidden="true">←</span> Kembali ke daftar pelatihan
            </button>
            <div>
              <span class="eyebrow">DETAIL PELATIHAN</span>
              <strong>${escapeHtml(state.selectedProgram.title)}</strong>
            </div>
          </section>
          <section class="training-access-panel">
            <div class="training-access-panel__heading">
              <div><span class="eyebrow">AKSES PER PELATIHAN</span><h2>${escapeHtml(state.selectedProgram.title)}</h2><p>${escapeHtml(state.selectedBatch.label)} · ${escapeHtml(state.enrollment.enrollment_number)}</p></div>
              <span class="badge badge--green">AKTIF</span>
            </div>
            <nav class="training-access-tabs" aria-label="Menu ${escapeHtml(state.selectedProgram.title)}">
              ${trainingSections
                .map(
                  ([section, label]) =>
                    `<button class="${state.trainingSection === section ? "is-active" : ""}" data-action="select-training-section" data-section="${section}" type="button">${label}</button>`,
                )
                .join("")}
            </nav>
            <div class="training-access-content">
              <span class="eyebrow">${escapeHtml(trainingSectionLabels[state.trainingSection] || "Ringkasan")}</span>
              <h3>${state.trainingSection === "overview" ? "Pelatihan siap diikuti" : `${escapeHtml(trainingSectionLabels[state.trainingSection])} ${escapeHtml(state.selectedProgram.code)}`}</h3>
              <p>${state.trainingSection === "overview" ? "Pembayaran telah lunas. Informasi materi, jadwal, kehadiran, logbook, kompetensi, dan sertifikat tersimpan khusus untuk program ini." : "Konten pada bagian ini mengikuti program dan batch yang sedang dipilih."}</p>
            </div>
          </section>
        `
        : "";
    const viewTitle = showingPrograms
      ? "Program Pelatihan"
      : showingApplications
        ? "Pendaftaran Saya"
        : showingApplicationDetail
          ? "Detail Status Pendaftaran"
        : showingPayments
          ? "Pembayaran"
          : showingTraining
            ? "Pelatihan Saya"
            : showingTrainingDetail
              ? "Detail Pelatihan"
            : showingHelp
              ? "Bantuan"
            : showingProfile
              ? "Profil & Data Diri"
              : "Dashboard Peserta";
    const viewDescription = showingPrograms
      ? "Bandingkan program dan pilih program berbeda yang ingin Anda ikuti."
      : showingApplications
        ? "Pantau proses persetujuan setiap program yang Anda daftarkan."
        : showingApplicationDetail
          ? `Status lengkap pendaftaran ${state.selectedProgram.title}.`
        : showingPayments
          ? "Buka kembali invoice dan status pembayaran untuk setiap program."
          : showingTraining
            ? "Akses program yang sudah lunas dan aktif secara terpisah."
            : showingTrainingDetail
              ? `Akses materi dan aktivitas khusus ${state.selectedProgram.title}.`
            : showingHelp
              ? `Tim ${branding.name} siap membantu pendaftaran dan pelatihan Anda.`
            : showingProfile
              ? "Kelola identitas peserta yang digunakan pada setiap pendaftaran pelatihan."
              : "Kelola seluruh program, pembayaran, dan pelatihan dari satu dashboard.";
    const viewContent = showingPrograms
      ? programSelection
      : showingApplications
        ? recordsPanel(
            "applications",
            state.applications,
            "Semua pendaftaran",
            "Pilih salah satu program untuk melihat prosesnya secara lengkap.",
          )
        : showingApplicationDetail
          ? applicationDetailPanel
        : showingPayments
          ? recordsPanel(
              "payments",
              paymentApplications,
              "Invoice dan pembayaran",
              "Invoice yang sudah lunas tetap dapat dibuka kembali kapan saja.",
            )
          : showingTraining
            ? recordsPanel(
                "training",
                activeEnrollments,
                "Pelatihan aktif",
                "Setiap program memiliki akses pelatihan dan jadwalnya sendiri.",
              )
            : showingTrainingDetail
              ? trainingAccessPanel
            : showingHelp
              ? '<section class="dashboard-start-card"><div><span class="eyebrow">PUSAT BANTUAN</span><h2>Butuh bantuan?</h2><p>Hubungi tim kami untuk bantuan pendaftaran, pembayaran, atau akses pelatihan.</p><a class="button button--primary" href="tel:0254123456">Hubungi 0254-123456</a></div></section>'
            : showingProfile
              ? profileContent
              : homeContent;

    return `
      <section class="${dashboardShellClasses("applicant-dashboard")}">
        ${participantSidebar(sidebarSection)}
        <button class="dashboard-sidebar-backdrop" data-action="toggle-dashboard-sidebar" type="button" aria-label="Tutup sidebar"></button>
        <div class="dashboard-body">
          <header>
            <div><span>Portal peserta</span><strong>${viewTitle}</strong></div>
            ${dashboardHeaderActions()}
          </header>
          <div class="dashboard-content">
            <section class="welcome-panel applicant-welcome">
              <div>
                <span>Selamat datang, ${escapeHtml(participantDisplayName())}</span>
                <h1>${viewTitle}</h1>
                <p>${viewDescription}</p>
              </div>
            </section>
            ${viewContent}
          </div>
        </div>
      </section>
    `;
  }

  function inputField(label, name, value, options) {
    const settings = options || {};
    const type = settings.type || "text";
    const required = settings.required === false ? "" : "required";
    return `
      <label class="field ${settings.full ? "field--full" : ""}">
        <span>${label}${required ? " *" : ""}</span>
        <input type="${type}" name="${name}" value="${escapeHtml(value)}" ${required}>
      </label>
    `;
  }

  function selectField(label, name, value, options) {
    return `
      <label class="field">
        <span>${label} *</span>
        <select name="${name}" required>
          ${options.map((option) => `<option ${option === value ? "selected" : ""}>${option}</option>`).join("")}
        </select>
      </label>
    `;
  }

  function renderProgramDashboardPage(
    activeSection,
    headerTitle,
    title,
    description,
    content,
  ) {
    return `
      <section class="${dashboardShellClasses("applicant-dashboard program-dashboard-page")}">
        ${participantSidebar(activeSection === "application" ? "applications" : activeSection)}
        <button class="dashboard-sidebar-backdrop" data-action="toggle-dashboard-sidebar" type="button" aria-label="Tutup sidebar"></button>
        <div class="dashboard-body">
          <header>
            <div><span>Katalog dan pendaftaran</span><strong>${headerTitle}</strong></div>
            ${dashboardHeaderActions()}
          </header>
          <div class="dashboard-content program-dashboard-page__content">
            <section class="dashboard-context-heading">
              <div>
                <span class="eyebrow">${activeSection === "programs" ? "PROGRAM PELATIHAN" : "PENDAFTARAN PESERTA"}</span>
                <h1>${title}</h1>
                <p>${description}</p>
              </div>
              <button class="button button--outline" data-action="back-program-list" type="button">Kembali ke Program</button>
            </section>
            ${content}
          </div>
        </div>
      </section>
    `;
  }

  function renderEnrollmentDashboard(activeStep, title, description, content) {
    return `
      <section class="${dashboardShellClasses("applicant-dashboard enrollment-dashboard")}">
        ${participantSidebar("applications")}
        <button class="dashboard-sidebar-backdrop" data-action="toggle-dashboard-sidebar" type="button" aria-label="Tutup sidebar"></button>
        <div class="dashboard-body">
          <header>
            <div><span>Proses pendaftaran</span><strong>${activeStep === 1 ? "Data Peserta" : "Dokumen Persyaratan"}</strong></div>
            ${dashboardHeaderActions()}
          </header>
          <div class="dashboard-content enrollment-dashboard__content">
            <section class="dashboard-registration-heading">
              <div>
                <span class="eyebrow">LANGKAH ${activeStep} DARI 2 · PENDAFTARAN PESERTA</span>
                <h1>${title}</h1>
                <p>${description}</p>
              </div>
              <button class="button button--outline" data-action="go-batch" type="button">Ubah Program / Batch</button>
            </section>
            <section class="dashboard-selection-strip" aria-label="Program dan batch yang dipilih">
              <div><span>Program</span><strong>${state.selectedProgram.title}</strong><small>${state.selectedProgram.subtitle}</small></div>
              <div><span>Batch</span><strong>${state.selectedBatch.label}</strong><small>${state.selectedBatch.start}</small></div>
              <div><span>Lokasi</span><strong>Workshop Cilegon</strong><small>${state.selectedBatch.location}</small></div>
            </section>
            ${content}
          </div>
        </div>
      </section>
    `;
  }

  function renderRegistrationPage() {
    const data = state.registration;
    return `
      ${pageHeader(
        "LANGKAH 3 DARI 4 · FORMULIR PENDAFTARAN",
        "Lengkapi data calon peserta",
        "Data ini digunakan untuk verifikasi kelayakan dan penerbitan dokumen pelatihan.",
      )}
      <section class="page-shell form-layout">
        <form class="form-card" data-form="registration">
          <div class="form-section">
            <div class="form-section__title"><span>01</span><div><h2>Informasi pribadi</h2><p>Gunakan data sesuai KTP atau dokumen identitas.</p></div></div>
            <div class="field-grid">
              ${inputField("Nama lengkap", "fullName", data.fullName, { full: true })}
              ${inputField("Tempat lahir", "birthPlace", data.birthPlace)}
              ${inputField("Tanggal lahir", "birthDate", data.birthDate, { type: "date" })}
              ${inputField("Email", "email", data.email, { type: "email" })}
              ${inputField("Nomor HP", "phone", data.phone)}
            </div>
          </div>
          <div class="form-section">
            <div class="form-section__title"><span>02</span><div><h2>Alamat dan pendidikan</h2><p>Informasi domisili dan latar belakang pendidikan.</p></div></div>
            <div class="field-grid">
              ${inputField("Alamat lengkap", "address", data.address, { full: true })}
              ${inputField("Kota / Kabupaten", "city", data.city)}
              ${selectField("Pendidikan terakhir", "education", data.education, ["SMP", "SMA/SMK", "Diploma", "Sarjana"])}
              ${selectField("Pengalaman welding", "experience", data.experience, ["Belum pernah", "Kurang dari 1 tahun", "1–3 tahun", "Lebih dari 3 tahun"])}
              <label class="field"><span>Ukuran wearpack *</span><select name="wearpack"><option>M</option><option selected>L</option><option>XL</option><option>XXL</option></select></label>
            </div>
          </div>
          <div class="form-section">
            <div class="form-section__title"><span>03</span><div><h2>Kontak darurat dan kelayakan</h2><p>Digunakan jika terjadi keadaan darurat selama pelatihan.</p></div></div>
            <div class="field-grid">
              ${inputField("Nama kontak darurat", "emergencyName", data.emergencyName)}
              ${inputField("Nomor kontak darurat", "emergencyPhone", data.emergencyPhone)}
            </div>
            <div class="eligibility-box">
              <strong>Konfirmasi kelayakan dasar</strong>
              <label><input type="checkbox" checked required><span>Saya berusia minimal 17 tahun.</span></label>
              <label><input type="checkbox" checked required><span>Saya sehat jasmani dan mampu mengikuti praktik workshop.</span></label>
              <label><input type="checkbox" checked required><span>Saya bersedia mengikuti seluruh rangkaian dan aturan keselamatan.</span></label>
            </div>
          </div>
          <div class="form-actions">
            <button class="button button--outline" data-action="back" type="button">← Kembali</button>
            <button class="button button--primary button--large" type="submit">Simpan &amp; Lanjut Upload <span>→</span></button>
          </div>
        </form>
        ${renderApplicationSidebar(1)}
      </section>
    `;
  }

  function renderRegistration() {
    return renderEnrollmentDashboard(
      1,
      "Lengkapi data calon peserta",
      "Data ini digunakan untuk verifikasi kelayakan dan penerbitan dokumen pelatihan.",
      `<div class="enrollment-embedded-page">${renderRegistrationPage()}</div>`,
    );
  }

  function renderApplicationSidebar(active) {
    const items = [
      ["Data peserta", active > 1 ? "Selesai" : active === 1 ? "Sedang diisi" : "Belum diisi"],
      ["Dokumen wajib", active > 2 ? "Selesai" : active === 2 ? "Sedang diisi" : "Belum diisi"],
      ["Review admin", active > 3 ? "Selesai" : active === 3 ? "Dalam proses" : "Menunggu"],
      ["Pembayaran", active >= 4 ? "Aktif" : "Terkunci"],
    ];
    return `
      <aside class="application-sidebar">
        <span class="eyebrow">STATUS PENDAFTARAN</span>
        <h2>${state.selectedProgram.title}</h2>
        <p>${state.selectedBatch.label}</p>
        <ol>
          ${items
            .map(
              ([label, status], index) => `
                <li class="${index + 1 < active ? "is-done" : ""} ${index + 1 === active ? "is-active" : ""}">
                  <span>${index + 1 < active ? "✓" : index + 1}</span>
                  <div><strong>${label}</strong><small>${status}</small></div>
                </li>
              `,
            )
            .join("")}
        </ol>
        <div class="support-box"><span>?</span><div><strong>Butuh bantuan?</strong><small>Tim kami siap membantu proses pendaftaran Anda.</small><a href="tel:0254123456">0254-123456</a></div></div>
      </aside>
    `;
  }

  function documentRow(id, title, description, required) {
    const uploaded = state.uploadedFiles[id];
    const uploadedName =
      typeof uploaded === "string" ? uploaded : uploaded?.name;
    return `
      <div class="upload-row ${uploaded ? "is-uploaded" : ""}">
        <span class="upload-row__icon">${uploaded ? "✓" : "▤"}</span>
        <div class="upload-row__copy">
          <div><h3>${title}</h3>${required ? '<span class="badge badge--red-soft">Wajib</span>' : '<span class="badge badge--gray">Opsional</span>'}</div>
          <p>${description}</p>
          ${uploaded ? `<small>${escapeHtml(uploadedName)} · Siap dikirim</small>` : ""}
        </div>
        <label class="upload-button">
          <input type="file" data-upload="${id}" accept=".jpg,.jpeg,.png,.pdf">
          ${uploaded ? "Ganti file" : "Pilih file"}
        </label>
      </div>
    `;
  }

  function renderDocumentsPage() {
    return `
      ${pageHeader(
        "LANGKAH 3 DARI 4 · UPLOAD DOKUMEN",
        "Unggah dokumen persyaratan",
        "Format JPG, PNG, atau PDF dengan ukuran maksimal 5 MB per dokumen.",
      )}
      <section class="page-shell form-layout">
        <div class="form-card">
          <div class="document-summary">
            <div><span>Dokumen wajib</span><strong>${Object.keys(state.uploadedFiles).filter((key) => ["id", "photo", "education"].includes(key)).length} / 3</strong></div>
            <div class="document-progress"><i style="width:${Math.min(100, Object.keys(state.uploadedFiles).length * 25)}%"></i></div>
          </div>
          <div class="upload-list">
            ${documentRow("id", "KTP atau identitas resmi", "Pastikan nama, NIK, dan foto terlihat jelas.", true)}
            ${documentRow("photo", "Pas foto terbaru", "Latar polos, tampak wajah dengan jelas.", true)}
            ${documentRow("education", "Ijazah pendidikan terakhir", "Minimal SMP atau sederajat.", true)}
            ${documentRow("certificate", "Sertifikat welding sebelumnya", "Jika pernah mengikuti pelatihan atau uji kompetensi.", false)}
          </div>
          <div class="notice notice--orange">
            <span>!</span>
            <div><strong>Pastikan dokumen dapat dibaca</strong><p>Dokumen yang buram atau terpotong dapat memperlambat proses verifikasi.</p></div>
          </div>
          <div class="form-actions">
            <button class="button button--outline" data-action="back" type="button">← Kembali</button>
            <button class="button button--primary button--large" data-action="submit-documents" type="button">Kirim untuk Diverifikasi <span>→</span></button>
          </div>
        </div>
        ${renderApplicationSidebar(2)}
      </section>
    `;
  }

  function renderDocuments() {
    return renderEnrollmentDashboard(
      2,
      "Unggah dokumen persyaratan",
      "Format JPG, PNG, atau PDF dengan ukuran maksimal 5 MB per dokumen.",
      `<div class="enrollment-embedded-page">${renderDocumentsPage()}</div>`,
    );
  }

  function renderSummary() {
    const program = state.selectedProgram;
    const adminFee = administrationFee;
    const total = program.price + adminFee;
    return `
      ${pageHeader(
        "LANGKAH 4 DARI 4 · RINGKASAN PESANAN",
        "Periksa pilihan dan biaya Anda",
        "Pastikan seluruh informasi sudah benar sebelum membuat invoice.",
      )}
      <section class="page-shell checkout-layout">
        <div class="checkout-main">
          <article class="checkout-card">
            <div class="checkout-card__title"><span>01</span><h2>Program dan batch</h2><button data-action="go-batch" type="button">Ubah</button></div>
            <div class="order-program">
              <span class="order-program__image" style="background-position:${program.imagePosition}"></span>
              <div><span class="badge badge--blue">${program.code}</span><h3>${program.title}</h3><p>${program.subtitle}</p></div>
            </div>
            <dl class="order-detail-grid">
              <div><dt>Batch</dt><dd>${state.selectedBatch.label}</dd></div>
              <div><dt>Periode</dt><dd>${state.selectedBatch.start} – ${state.selectedBatch.end}</dd></div>
              <div><dt>Jadwal</dt><dd>${state.selectedBatch.schedule}</dd></div>
              <div><dt>Lokasi</dt><dd>${state.selectedBatch.location}</dd></div>
            </dl>
          </article>
          <article class="checkout-card">
            <div class="checkout-card__title"><span>02</span><h2>Data peserta</h2><button data-action="go-registration" type="button">Ubah</button></div>
            <dl class="order-detail-grid">
              <div><dt>Nama lengkap</dt><dd>${state.registration.fullName}</dd></div>
              <div><dt>Email</dt><dd>${state.registration.email}</dd></div>
              <div><dt>Nomor HP</dt><dd>${state.registration.phone}</dd></div>
              <div><dt>Pendidikan</dt><dd>${state.registration.education}</dd></div>
            </dl>
          </article>
          <article class="refund-policy">
            <div><span>↶</span><div><h3>Kebijakan pembatalan &amp; refund</h3><p>Pelajari aturan sebelum melanjutkan pembayaran.</p></div></div>
            <table>
              <tbody>
                <tr><td>Batch dibatalkan ${escapeHtml(branding.name)}</td><td>Refund 100%</td></tr>
                <tr><td>Pembatalan minimal H-7</td><td>Refund 90%</td></tr>
                <tr><td>Pembatalan H-6 sampai H-3</td><td>Refund 50%</td></tr>
                <tr><td>Kurang dari H-3 / pelatihan dimulai</td><td>Tidak ada refund</td></tr>
              </tbody>
            </table>
          </article>
        </div>
        <aside class="price-card">
          <span class="eyebrow">RINCIAN BIAYA</span>
          <dl>
            <div><dt>Biaya program</dt><dd>${money(program.price)}</dd></div>
            <div><dt>Biaya administrasi</dt><dd>${money(adminFee)}</dd></div>
            <div class="discount"><dt>Diskon early bird</dt><dd>− ${money(0)}</dd></div>
          </dl>
          <div class="price-card__total"><span>Total pembayaran</span><strong>${money(total)}</strong></div>
          <label class="agreement"><input type="checkbox" id="order-agreement" checked><span>Saya telah membaca dan menyetujui kebijakan pembatalan, refund, serta tata tertib pelatihan.</span></label>
          <button class="button button--primary button--large button--full" data-action="create-invoice" type="button">Buat Invoice <span>→</span></button>
          <small class="price-note">Kursi akan ditahan selama 24 jam setelah invoice dibuat.</small>
        </aside>
      </section>
    `;
  }

  function renderInvoice() {
    const invoice = state.invoice;

    if (!invoice) {
      return `
        ${pageHeader(
          "INVOICE",
          "Invoice belum tersedia",
          "Invoice dapat dibuat setelah pendaftaran Anda disetujui admin.",
        )}
        <section class="page-shell">
          <div class="empty-state">
            <span>i</span>
            <h2>Belum ada invoice</h2>
            <p>Kembali ke dashboard untuk memeriksa status pendaftaran Anda.</p>
            <button class="button button--primary" data-action="show-dashboard-home" type="button">Kembali ke Dashboard</button>
          </div>
        </section>
      `;
    }

    const dueAt = invoice.due_at ? new Date(invoice.due_at) : null;
    const isExpired =
      invoice.status === "unpaid" && dueAt && dueAt.getTime() <= Date.now();
    const displayStatus = isExpired ? "expired" : invoice.status;
    const statusLabels = {
      unpaid: "MENUNGGU PEMBAYARAN",
      paid: "LUNAS",
      expired: "KEDALUWARSA",
      cancelled: "DIBATALKAN",
      refunded: "DIKEMBALIKAN",
    };
    const statusClass =
      displayStatus === "paid"
        ? "badge--green"
        : displayStatus === "unpaid"
          ? "badge--orange-soft"
          : "badge--red-soft";
    const total = Number(invoice.total_amount || 0);
    const subtotal = Number(invoice.subtotal || 0);
    const invoiceAdministrationFee = Number(
      invoice.administration_fee || 0,
    );
    const discount = Number(invoice.discount_amount || 0);

    return `
      ${pageHeader(
        `INVOICE · ${escapeHtml(invoice.invoice_number)}`,
        displayStatus === "paid"
          ? "Pembayaran telah diterima"
          : displayStatus === "unpaid"
            ? "Selesaikan pembayaran sebelum batas waktu"
            : "Invoice tidak dapat dibayar",
        displayStatus === "unpaid"
          ? "Invoice telah dibuat dan kursi Anda sedang kami amankan."
          : displayStatus === "paid"
            ? "Kursi pelatihan Anda telah diamankan."
            : "Hubungi admin jika Anda memerlukan bantuan.",
      )}
      <section class="page-shell invoice-layout">
        <article class="invoice-paper">
          <div class="invoice-paper__header">
            <div><span class="eyebrow">INVOICE</span><h2>${escapeHtml(invoice.invoice_number)}</h2><p>Dibuat ${escapeHtml(formatDateTime(invoice.issued_at))}</p></div>
            <span class="badge ${statusClass}">${statusLabels[displayStatus] || escapeHtml(displayStatus)}</span>
          </div>
          <div class="reservation-banner">
            <span>◷</span>
            <div><small>BATAS WAKTU PEMBAYARAN</small><strong id="countdown">${displayStatus === "unpaid" ? "--:--:--" : "00:00:00"}</strong></div>
            <p>${invoice.due_at ? `Sampai ${escapeHtml(formatDateTime(invoice.due_at))}.` : "Ikuti batas pembayaran yang ditentukan admin."}</p>
          </div>
          <div class="invoice-address">
            <div><span>DITAGIHKAN KEPADA</span><strong>${state.registration.fullName}</strong><p>${state.registration.email}<br>${state.registration.phone}</p></div>
            <div><span>PENYELENGGARA</span><strong>${escapeHtml(branding.name)}</strong><p>Jl. Industri No. 88<br>Cilegon, Banten 42435</p></div>
          </div>
          <table class="invoice-table">
            <thead><tr><th>Deskripsi</th><th>Jumlah</th></tr></thead>
            <tbody>
              <tr><td><strong>${state.selectedProgram.title}</strong><small>${state.selectedBatch.label} · ${state.selectedBatch.start}</small></td><td>${money(subtotal)}</td></tr>
              <tr><td>Biaya administrasi</td><td>${money(invoiceAdministrationFee)}</td></tr>
              ${discount > 0 ? `<tr><td>Diskon</td><td>− ${money(discount)}</td></tr>` : ""}
            </tbody>
            <tfoot><tr><td>Total tagihan</td><td>${money(total)}</td></tr></tfoot>
          </table>
          <div class="invoice-notes"><span>i</span><p>Simpan nomor invoice ini. Status pembayaran akan diperbarui otomatis setelah transaksi berhasil.</p></div>
        </article>
        <aside class="payment-cta-card">
          <span class="payment-cta-card__icon">▰</span>
          <h2>Pilih metode pembayaran</h2>
          <p>Virtual account, QRIS, dan transfer bank tersedia.</p>
          <div class="payment-cta-card__amount"><span>Total tagihan</span><strong>${money(total)}</strong></div>
          ${
            displayStatus === "unpaid"
              ? '<button class="button button--primary button--large button--full" data-action="next" type="button">Lanjut Pembayaran <span>→</span></button>'
              : '<button class="button button--primary button--large button--full" data-action="show-dashboard-payments" type="button">Kembali ke Pembayaran</button>'
          }
          ${displayStatus === "paid" ? '<button class="button button--outline button--full" data-action="show-dashboard-programs" type="button">Lihat Program Lain</button>' : ""}
          <button class="button button--outline button--full" data-action="download-invoice" type="button">Unduh Invoice</button>
          <small>Butuh bantuan? Hubungi 0254-123456</small>
        </aside>
      </section>
    `;
  }

  function renderPayment() {
    const invoice = state.invoice;
    if (!invoice) return renderInvoice();

    const total = Number(invoice.total_amount || 0);
    const subtotal = Number(invoice.subtotal || 0);
    const invoiceAdministrationFee = Number(
      invoice.administration_fee || 0,
    );

    if (invoice.status === "paid") {
      return `
        ${pageHeader(
          `PEMBAYARAN · ${escapeHtml(invoice.invoice_number)}`,
          "Pembayaran telah dikonfirmasi",
          "Rincian pembayaran program ini tetap dapat Anda lihat kapan saja.",
        )}
        <section class="page-shell payment-layout">
          <article class="checkout-card">
            <div class="notice notice--green"><span>✓</span><div><strong>Status pembayaran: Lunas</strong><p>Kursi pelatihan telah diamankan dan akses pelatihan sudah aktif.</p></div></div>
            <div class="order-program">
              <span class="order-program__image" style="background-position:${state.selectedProgram.imagePosition}"></span>
              <div><span class="badge badge--blue">${state.selectedProgram.code}</span><h3>${state.selectedProgram.title}</h3><p>${state.selectedBatch.label}</p></div>
            </div>
            <dl class="summary-list">
              <div><dt>Nomor invoice</dt><dd>${escapeHtml(invoice.invoice_number)}</dd></div>
              <div><dt>Tanggal pembayaran</dt><dd>${formatDateTime(invoice.paid_at)}</dd></div>
              <div><dt>Total dibayar</dt><dd>${money(total)}</dd></div>
            </dl>
          </article>
          <aside class="payment-summary">
            <span class="badge badge--green">LUNAS</span>
            <h2>${state.selectedProgram.title}</h2>
            <p>${state.selectedBatch.label}</p>
            <div class="payment-summary__total"><span>Total pembayaran</span><strong>${money(total)}</strong></div>
            <button class="button button--primary button--large button--full" data-action="show-dashboard-training" type="button">Buka Pelatihan Saya <span>→</span></button>
            <button class="button button--outline button--full" data-action="show-dashboard-programs" type="button">Lihat Program Lain</button>
            <button class="text-button" data-action="show-dashboard-payments" type="button">← Kembali ke daftar pembayaran</button>
          </aside>
        </section>
      `;
    }

    const methods = [
      ["va-bca", "BCA Virtual Account", "Konfirmasi otomatis", "BCA"],
      ["va-mandiri", "Mandiri Virtual Account", "Konfirmasi otomatis", "MDR"],
      ["qris", "QRIS", "GoPay, OVO, DANA, mobile banking", "QR"],
      ["other-va", "Virtual Account Bank Lain", "BNI, BRI, dan Permata", "VA"],
    ];
    return `
      ${pageHeader(
        `PEMBAYARAN · ${escapeHtml(invoice.invoice_number)}`,
        "Pilih metode pembayaran",
        "Pembayaran aman dan status diperbarui otomatis.",
      )}
      <section class="page-shell payment-layout">
        <div class="payment-methods">
          <div class="payment-heading"><h2>Metode pembayaran</h2><span>Semua transaksi terenkripsi</span></div>
          <div class="method-list">
            ${methods
              .map(
                ([id, title, desc, mark]) => `
                  <button class="payment-method ${state.paymentMethod === id ? "is-selected" : ""}" data-action="select-payment" data-id="${id}" type="button">
                    <span class="radio-mark"></span>
                    <span class="payment-logo">${mark}</span>
                    <div><strong>${title}</strong><small>${desc}</small></div>
                    ${id !== "transfer" ? '<span class="badge badge--green">OTOMATIS</span>' : ""}
                  </button>
                `,
              )
              .join("")}
          </div>
          ${
            state.paymentMethod === "qris"
              ? `<div class="method-info"><span>QR</span><p>QR pembayaran akan tampil setelah Anda menekan tombol bayar. Kode berlaku selama 15 menit.</p></div>`
              : `<div class="method-info"><span>i</span><p>Nomor virtual account atau instruksi transfer akan dibuat khusus untuk transaksi ini.</p></div>`
          }
        </div>
        <aside class="payment-summary">
          <span class="eyebrow">RINGKASAN PEMBAYARAN</span>
          <div class="payment-summary__program"><span class="summary-program__thumb"></span><div><strong>${state.selectedProgram.title}</strong><small>${state.selectedBatch.label}</small></div></div>
          <dl>
            <div><dt>Subtotal</dt><dd>${money(subtotal)}</dd></div>
            <div><dt>Administrasi</dt><dd>${money(invoiceAdministrationFee)}</dd></div>
          </dl>
          <div class="payment-summary__total"><span>Total</span><strong>${money(total)}</strong></div>
          <button class="button button--primary button--large button--full" data-action="pay-now" type="button">Bayar Sekarang <span>→</span></button>
          <button class="text-button" data-action="back" type="button">← Kembali ke invoice</button>
          <div class="payment-security"><span>◇</span><p>Pembayaran diproses melalui kanal resmi dan aman.</p></div>
        </aside>
      </section>
    `;
  }

  function renderSuccess() {
    return `
      <section class="success-page">
        <div class="success-card">
          <div class="success-icon"><span>✓</span></div>
          <span class="eyebrow">PEMBAYARAN BERHASIL</span>
          <h1>Selamat, Anda resmi menjadi peserta!</h1>
          <p>Pembayaran telah terkonfirmasi dan kursi pada ${state.selectedBatch.label} sudah diamankan.</p>
          <div class="success-receipt">
            <div><span>Nomor transaksi</span><strong>WS-PAY-260723-0921</strong></div>
            <div><span>Program</span><strong>${state.selectedProgram.title}</strong></div>
            <div><span>Batch</span><strong>${state.selectedBatch.label}</strong></div>
            <div><span>Total dibayar</span><strong>${money(state.invoice?.total_amount || 0)}</strong></div>
          </div>
          <div class="next-steps">
            <h2>Langkah berikutnya</h2>
            <div><span>1</span><p><strong>Periksa email Anda</strong><small>Kuitansi dan detail pelatihan telah dikirim.</small></p></div>
            <div><span>2</span><p><strong>Lengkapi profil peserta</strong><small>Pastikan data untuk sertifikat sudah benar.</small></p></div>
            <div><span>3</span><p><strong>Masuk ke dashboard</strong><small>Lihat jadwal, materi orientasi, dan pengumuman.</small></p></div>
          </div>
          <button class="button button--primary button--large" data-action="next" type="button">Masuk Dashboard Peserta <span>→</span></button>
          <button class="button button--outline" data-action="download-receipt" type="button">Unduh Kuitansi</button>
        </div>
      </section>
    `;
  }

  function render() {
    const id = steps[state.step].id;
    const views = {
      home: renderHome,
      about: renderAbout,
      programs: renderPrograms,
      news: renderNews,
      article: renderArticle,
      alumni: renderAlumni,
      recruiters: renderRecruiters,
      certificate: renderCertificate,
      "member-programs": renderMemberPrograms,
      detail: renderDetail,
      batch: renderBatch,
      account: renderAccount,
      verification: renderVerification,
      registration: renderRegistration,
      documents: renderDocuments,
      summary: renderSummary,
      invoice: renderInvoice,
      payment: renderPayment,
      success: renderSuccess,
      dashboard: renderMemberPrograms,
    };
    app.innerHTML = views[id]();
    document.querySelectorAll("[data-public-route]").forEach((link) => {
      const activeRoute =
        id === "home"
          ? "home"
          : ["programs", "detail", "batch"].includes(id)
            ? "programs"
            : id === "article"
              ? "news"
              : ["about", "news", "alumni", "recruiters", "certificate"].includes(id)
                ? id
                : "";
      link.classList.toggle("is-active", link.dataset.publicRoute === activeRoute);
    });
    document.body.classList.toggle(
      "dashboard-mode",
      ["dashboard", "member-programs", "registration", "documents"].includes(id) ||
        (state.loggedIn && ["detail", "batch"].includes(id)),
    );
    app.querySelectorAll("[data-user-avatar]").forEach((image) => {
      image.addEventListener("error", () => image.remove(), { once: true });
    });
    if (id === "invoice") startInvoiceTimer();
  }

  function startInvoiceTimer() {
    window.clearInterval(invoiceTimer);

    const countdown = document.getElementById("countdown");
    const dueAt = state.invoice?.due_at
      ? new Date(state.invoice.due_at).getTime()
      : null;

    if (!countdown || !dueAt || state.invoice?.status !== "unpaid") return;

    const updateCountdown = () => {
      const seconds = Math.max(0, Math.floor((dueAt - Date.now()) / 1000));
      const hours = String(Math.floor(seconds / 3600)).padStart(2, "0");
      const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, "0");
      const remaining = String(seconds % 60).padStart(2, "0");
      countdown.textContent = `${hours}:${minutes}:${remaining}`;

      if (seconds === 0) {
        window.clearInterval(invoiceTimer);
      }
    };

    updateCountdown();
    invoiceTimer = window.setInterval(updateCountdown, 1000);
  }

  function storeForm(form) {
    const data = new FormData(form);
    for (const [key, value] of data.entries()) {
      if (key in state.registration && typeof value === "string") {
        state.registration[key] = value;
      }
    }
  }

  document.addEventListener("submit", async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    event.preventDefault();
    if (!form.reportValidity()) return;

    if (form.dataset.form === "newsletter") {
      form.reset();
      showToast("Terima kasih. Anda sudah terdaftar untuk Alpha Update.", "success");
      return;
    }

    if (form.dataset.form === "certificate-verification") {
      const result = form.parentElement?.querySelector("#certificate-result");
      if (result) result.hidden = false;
      showToast("Sertifikat ditemukan dan berstatus valid.", "success");
      return;
    }

    if (form.dataset.form === "account") {
      storeForm(form);
      const submitButton = form.querySelector('button[type="submit"]');
      const submitLabel = submitButton?.dataset.submitLabel || "Memproses";

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<span class="button-spinner"></span> Memproses...';
      }

      try {
        const data = new FormData(form);
        const isRegister = state.accountMode === "register";
        const payload = isRegister
          ? {
              username: data.get("username"),
              email: data.get("email"),
              password: data.get("password"),
              password_confirmation: data.get("password_confirmation"),
              agreement: data.has("agreement"),
            }
          : {
              login: data.get("login"),
              password: data.get("password"),
              remember: data.has("remember"),
            };
        const result = await backendRequest(
          isRegister ? backend.routes?.register : backend.routes?.login,
          payload,
        );

        if (result.requires_verification) {
          beginEmailVerification(result);
          return;
        }

        setAuthenticatedUser(result.user);
        if (continueAfterAuthentication(result)) return;
        state.dashboardView = "home";
        navigate("member-programs");
        await loadCurrentApplication();
        showToast(result.message || "Anda berhasil masuk.", "success");
      } catch (error) {
        if (error.data?.requires_verification) {
          beginEmailVerification(error.data);
          return;
        }

        showToast(error.message, "danger");
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = `${submitLabel} <span>→</span>`;
        }
      }
    }

    if (form.dataset.form === "verification") {
      const submitButton = form.querySelector('button[type="submit"]');
      const submitLabel = submitButton?.dataset.submitLabel || "Verifikasi Email";

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<span class="button-spinner"></span> Memverifikasi...';
      }

      try {
        const data = new FormData(form);
        const result = await backendRequest(backend.routes?.verifyEmail, {
          code: data.get("code"),
        });

        setAuthenticatedUser(result.user);
        if (continueAfterAuthentication(result)) return;
        state.verification.pending = false;
        state.verification.debugCode = "";
        state.dashboardView = "home";
        navigate("member-programs");
        await loadCurrentApplication();
        showToast(result.message || "Email berhasil diverifikasi.", "success");
      } catch (error) {
        showToast(error.message, "danger");
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = `${submitLabel} <span>→</span>`;
        }
      }
    }

    if (form.dataset.form === "profile") {
      storeForm(form);
      const submitButton = form.querySelector('button[type="submit"]');
      const submitLabel =
        submitButton?.dataset.submitLabel || "Simpan Profil & Data Diri";

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<span class="button-spinner"></span> Menyimpan...';
      }

      try {
        const result = await backendRequest(backend.routes?.profileStore, {
          username: state.registration.username,
          full_name: state.registration.fullName,
          phone: state.registration.phone,
          identity_type: state.registration.identityType,
          identity_number: state.registration.identityNumber,
          birth_place: state.registration.birthPlace,
          birth_date: state.registration.birthDate,
          gender: state.registration.gender,
          address: state.registration.address,
          city: state.registration.city,
          province: state.registration.province,
          postal_code: state.registration.postalCode,
          last_education: state.registration.education,
          occupation: state.registration.occupation,
          emergency_contact_name: state.registration.emergencyName,
          emergency_contact_phone: state.registration.emergencyPhone,
        });

        applyParticipantProfile(result.profile);
        showToast(
          result.message || "Profil dan data diri berhasil disimpan.",
          "success",
        );

        if (state.profileContinueToRegistration) {
          state.profileContinueToRegistration = false;
          navigate("registration");
        } else {
          state.dashboardView = "profile";
          render();
        }
      } catch (error) {
        showToast(error.message, "danger");
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.innerHTML = `${submitLabel} <span>→</span>`;
        }
      }
    }

    if (form.dataset.form === "registration") {
      if (!state.registration.profileComplete) {
        state.profileContinueToRegistration = true;
        state.dashboardView = "profile";
        navigate("member-programs");
        showToast(
          "Lengkapi Profil & Data Diri sebelum mengunggah dokumen.",
          "warning",
        );
        return;
      }
      storeForm(form);
      showToast("Data peserta berhasil disimpan.", "success");
      navigate("documents");
    }
  });

  document.addEventListener("click", async (event) => {
    const selectedProfileMenu = event.target.closest(".profile-menu");
    if (!selectedProfileMenu) closeProfileMenus();

    const button = event.target.closest("[data-action]");
    if (!button) return;
    const action = button.dataset.action;

    if (action === "toggle-dashboard-sidebar") {
      if (window.matchMedia("(max-width: 620px)").matches) {
        state.dashboardSidebarOpen = !state.dashboardSidebarOpen;
      } else {
        state.dashboardSidebarCollapsed =
          !state.dashboardSidebarCollapsed;
        window.localStorage.setItem(
          "welding-participant-sidebar",
          state.dashboardSidebarCollapsed ? "collapsed" : "expanded",
        );
      }
      render();
      return;
    }

    if (action === "toggle-profile-menu") {
      const menu = button.closest(".profile-menu");
      const dropdown = menu?.querySelector(".profile-dropdown");
      if (!menu || !dropdown) return;

      const willOpen = dropdown.hidden;
      closeProfileMenus(menu);
      dropdown.hidden = !willOpen;
      button.setAttribute("aria-expanded", String(willOpen));
      return;
    }

    if (action === "next") {
      if (steps[state.step].id === "success") {
        state.dashboardView = "training";
        navigate("member-programs");
      } else if (
        steps[state.step].id === "batch" &&
        !state.registration.profileComplete
      ) {
        state.profileContinueToRegistration = true;
        state.dashboardView = "profile";
        navigate("member-programs");
        showToast(
          "Lengkapi Profil & Data Diri sebelum melanjutkan pendaftaran.",
          "warning",
        );
      } else {
        navigate(state.step + 1);
      }
    }
    if (action === "back") navigate(state.step - 1);
    if (action === "go-home") {
      event.preventDefault();
      navigate("home");
    }
    if (action === "go-programs") {
      event.preventDefault();
      navigate("programs");
    }
    if (action === "go-public-page") {
      event.preventDefault();
      navigate(button.dataset.target || "home");
    }
    if (action === "open-article") {
      state.selectedArticle =
        academyNews.find((article) => article.id === button.dataset.id) ||
        academyNews[0];
      navigate("article");
    }
    if (action === "fill-certificate") {
      const input = document.getElementById("certificate-number");
      if (input) {
        input.value = "AA-SMAW-2026-00128";
        input.focus();
      }
    }
    if (action === "alumni-login") {
      showToast("Tampilan login alumni akan diaktifkan pada fase implementasi.", "info");
    }
    if (action === "recruiter-demo" || action === "proposal-interest") {
      showToast("Form minat dan kontak akan dihubungkan pada fase berikutnya.", "info");
    }
    if (action === "load-more-news") {
      showToast("Seluruh berita contoh sudah ditampilkan.", "info");
    }
    if (action === "go-home-section") {
      event.preventDefault();
      const targetId = button.dataset.target;
      navigate("home");
      window.setTimeout(() => {
        document
          .getElementById(targetId)
          ?.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 50);
    }
    if (action === "go-account") {
      event.preventDefault();
      navigate(state.loggedIn ? "member-programs" : "account");
    }
    if (action === "go-batch") navigate("batch");
    if (action === "go-registration") {
      if (!state.registration.profileComplete) {
        state.profileContinueToRegistration = true;
        state.dashboardView = "profile";
        navigate("member-programs");
        showToast(
          "Lengkapi Profil & Data Diri sebelum melanjutkan pendaftaran.",
          "warning",
        );
      } else {
        navigate("registration");
      }
    }

    if (action === "select-program" || action === "quick-select-program") {
      const selectedProgram =
        programs.find((program) => program.id === button.dataset.id) ||
        programs[0];
      const existingApplication = applicationForProgram(selectedProgram);

      if (action === "quick-select-program" && existingApplication) {
        selectApplication(existingApplication);
        state.dashboardView = existingApplication.invoice
          ? "payments"
          : "applications";
        navigate("member-programs");
        showToast(
          "Anda sudah terdaftar pada program ini. Status program dibuka.",
          "info",
        );
        return;
      }

      state.selectedProgram = selectedProgram;
      batches = programBatches(state.selectedProgram);
      state.selectedBatch = batches[0];
      state.application = null;
      state.applicationStatus = "not-started";
      state.invoice = null;
      state.enrollment = null;
      state.uploadedFiles = {};
      state.programOrigin = button.closest(".applicant-dashboard")
        ? "dashboard"
        : "public";
      if (action === "select-program") {
        navigate("detail");
      } else if (state.loggedIn) {
        navigate("batch");
      } else {
        navigate("account");
        showToast(
          "Masuk atau buat akun untuk memilih jadwal program.",
          "info",
        );
      }
    }

    if (action === "select-detail-program") {
      if (state.loggedIn) {
        const existingApplication = applicationForProgram(
          state.selectedProgram,
        );
        if (existingApplication) {
          selectApplication(existingApplication);
          state.dashboardView = "applications";
          navigate("member-programs");
          showToast(
            "Anda sudah terdaftar pada program ini. Pilih program berbeda untuk pendaftaran baru.",
            "info",
          );
          return;
        }
        navigate("batch");
      } else {
        navigate("account");
        showToast(
          "Masuk atau buat akun untuk melanjutkan pendaftaran.",
          "info",
        );
      }
    }

    if (action === "back-program-list") {
      if (state.loggedIn) {
        state.dashboardView = "programs";
        navigate("member-programs");
      } else {
        navigate("programs");
      }
    }

    if (action === "select-batch") {
      state.selectedBatch =
        batches.find((batch) => batch.id === button.dataset.id) || batches[0];
      render();
      showToast(`${state.selectedBatch.label} dipilih.`, "success");
    }

    if (action === "account-mode") {
      state.accountMode = button.dataset.mode;
      render();
    }

    if (action === "resend-verification") {
      button.disabled = true;
      try {
        const result = await backendRequest(
          backend.routes?.resendVerification,
        );
        state.verification.debugCode = result.debug_code || "";
        render();
        showToast(result.message || "Kode baru telah dikirim.", "success");
      } catch (error) {
        button.disabled = false;
        showToast(error.message, "danger");
      }
    }

    if (action === "back-to-account") {
      navigate("account");
    }

    if (action === "submit-documents") {
      const required = ["id", "photo", "education"];
      const missing = required.filter((id) => !state.uploadedFiles[id]);
      if (missing.length) {
        showToast(
          `Lengkapi ${missing.length} dokumen wajib sebelum melanjutkan.`,
          "warning",
        );
        return;
      }
      if (!state.selectedProgram.databaseId || !state.selectedBatch.databaseId) {
        showToast(
          "Program atau batch ini belum dibuka oleh admin. Silakan pilih jadwal lain.",
          "warning",
        );
        return;
      }

      button.disabled = true;
      button.innerHTML =
        '<span class="button-spinner"></span> Mengirim pendaftaran...';

      try {
        const formData = new FormData();
        formData.append("training_program_id", state.selectedProgram.databaseId);
        formData.append("training_batch_id", state.selectedBatch.databaseId);
        formData.append("full_name", state.registration.fullName);
        formData.append("phone", state.registration.phone);
        formData.append("birth_place", state.registration.birthPlace);
        formData.append("birth_date", state.registration.birthDate);
        formData.append("address", state.registration.address);
        formData.append("city", state.registration.city);
        formData.append("education", state.registration.education);
        formData.append("experience", state.registration.experience);
        formData.append("emergency_name", state.registration.emergencyName);
        formData.append(
          "emergency_phone",
          state.registration.emergencyPhone,
        );
        Object.entries(state.uploadedFiles).forEach(([type, uploaded]) => {
          const file = uploaded?.file;
          if (file) formData.append(`documents[${type}]`, file, file.name);
        });

        const result = await backendFormRequest(
          backend.routes?.applicationStore,
          formData,
        );
        applyApplication(result.application);
        state.uploadedFiles = {};
        state.dashboardView = "applications";
        navigate("member-programs");
        showToast(result.message, "success");
      } catch (error) {
        button.disabled = false;
        button.innerHTML = "Kirim untuk Diverifikasi <span>→</span>";
        showToast(error.message, "danger");
      }
    }

    if (action === "refresh-application") {
      loadCurrentApplication(true);
    }

    if (action === "continue-approved") {
      navigate(
        state.invoice?.status === "paid"
          ? "payment"
          : state.invoice
            ? "invoice"
            : "summary",
      );
    }

    if (action === "show-dashboard-home") {
      state.dashboardView = "home";
      navigate("member-programs");
    }

    if (action === "show-dashboard-programs") {
      state.dashboardView = "programs";
      navigate("member-programs");
    }

    if (action === "show-dashboard-applications") {
      state.dashboardView = "applications";
      navigate("member-programs");
    }

    if (action === "show-dashboard-payments") {
      state.dashboardView = "payments";
      navigate("member-programs");
    }

    if (action === "show-dashboard-training") {
      state.dashboardView = "training";
      navigate("member-programs");
    }

    if (action === "back-to-applications") {
      state.dashboardView = "applications";
      navigate("member-programs");
    }

    if (action === "back-to-training") {
      state.dashboardView = "training";
      navigate("member-programs");
    }

    if (action === "show-dashboard-help") {
      state.dashboardView = "help";
      navigate("member-programs");
    }

    if (action === "show-dashboard-profile") {
      closeProfileMenus();
      state.profileContinueToRegistration = false;
      state.dashboardView = "profile";
      navigate("member-programs");
    }

    if (action === "select-training-section") {
      state.trainingSection = button.dataset.section || "overview";
      render();
    }

    if (
      ["open-application-record", "open-payment-record", "open-training-record"].includes(
        action,
      )
    ) {
      const application = state.applications.find(
        (item) =>
          Number(item.id) === Number(button.dataset.applicationId),
      );
      if (!application) return;

      selectApplication(application);
      if (action === "open-payment-record") {
        navigate(
          application.invoice?.status === "paid"
            ? "payment"
            : application.invoice
              ? "invoice"
              : "summary",
        );
      } else {
        state.dashboardView =
          action === "open-training-record"
            ? "training-detail"
            : "application-detail";
        if (action === "open-training-record") {
          state.trainingSection = "overview";
        }
        navigate("member-programs");
      }
    }

    if (action === "create-invoice") {
      const agreement = document.getElementById("order-agreement");
      if (agreement && !agreement.checked) {
        showToast("Setujui kebijakan sebelum membuat invoice.", "warning");
        return;
      }

      button.disabled = true;
      button.innerHTML =
        '<span class="button-spinner"></span> Membuat invoice...';

      try {
        const result = await backendRequest(backend.routes?.invoiceStore, {
          agreement: true,
          training_application_id: state.application?.id,
        });
        state.invoice = result.invoice;
        if (state.application) state.application.invoice = result.invoice;
        navigate("invoice");
        showToast(result.message, "success");
      } catch (error) {
        button.disabled = false;
        button.innerHTML = "Buat Invoice <span>→</span>";
        showToast(error.message, "danger");
      }
    }

    if (action === "select-payment") {
      state.paymentMethod = button.dataset.id;
      render();
    }

    if (action === "pay-now") {
      button.disabled = true;
      button.innerHTML =
        '<span class="button-spinner"></span> Membuka Midtrans...';

      try {
        const result = await backendRequest(backend.routes?.paymentStore, {
          invoice_id: state.invoice?.id,
          payment_method: state.paymentMethod,
        });
        showToast(result.message, "success");
        window.location.assign(result.redirect_url);
      } catch (error) {
        button.disabled = false;
        button.innerHTML = "Bayar Sekarang <span>→</span>";
        showToast(error.message, "danger");
      }
    }

    if (action === "download-invoice" || action === "download-receipt") {
      showToast(
        "File PDF akan dibuat oleh backend pada versi terintegrasi.",
        "info",
      );
    }

    if (action === "verify-certificate") {
      showToast(
        "Form verifikasi sertifikat akan dihubungkan pada tahap backend berikutnya.",
        "info",
      );
    }

    if (action === "scroll-programs") {
      event.preventDefault();
      document
        .getElementById("program-list")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    if (action === "logout") {
      button.disabled = true;
      try {
        const result = await backendRequest(backend.routes?.logout);
        state.loggedIn = false;
        state.dashboardView = "home";
        applyParticipantProfile({});
        state.registration.email = "";
        state.registration.avatar = "";
        state.registration.profileComplete = false;
        state.applications = [];
        state.application = null;
        state.applicationStatus = "not-started";
        state.invoice = null;
        state.enrollment = null;
        state.accountMode = "login";
        state.verification.pending = false;
        state.verification.email = "";
        state.verification.debugCode = "";
        navigate("account");
        showToast(result.message || "Anda telah keluar dari akun.", "info");
      } catch (error) {
        button.disabled = false;
        showToast(error.message, "danger");
      }
    }

    if (action === "toggle-menu") {
      const nav = document.querySelector(".public-nav");
      const isOpen = nav.classList.toggle("is-open");
      button.setAttribute("aria-expanded", String(isOpen));
      button.textContent = isOpen ? "×" : "☰";
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeProfileMenus();
  });

  document.addEventListener("input", (event) => {
    if (event.target.id === "verification-code") {
      event.target.value = event.target.value.replace(/\D/g, "").slice(0, 6);
    }

    if (event.target.id === "program-search") {
      state.search = event.target.value;
      render();
      const input = document.getElementById("program-search");
      input.focus();
      input.setSelectionRange(state.search.length, state.search.length);
    }
  });

  document.addEventListener("change", (event) => {
    if (event.target.id === "level-filter") {
      state.level = event.target.value;
      render();
    }

    if (event.target.matches("[data-upload]")) {
      const file = event.target.files[0];
      if (!file) return;
      state.uploadedFiles[event.target.dataset.upload] = {
        name: file.name,
        file,
      };
      render();
      showToast(`${file.name} berhasil dipilih.`, "success");
    }
  });

  const initialStep = steps.findIndex(
    (step) => `#${step.id}` === window.location.hash,
  );
  if (state.loggedIn && backend.auth?.user?.is_admin) {
    window.location.replace(backend.routes?.admin || "/admin");
    return;
  }
  const initialPublicRoutes = [
    "home",
    "about",
    "programs",
    "detail",
    "news",
    "article",
    "alumni",
    "recruiters",
    "certificate",
    "account",
  ];
  if (state.verification.pending) initialPublicRoutes.push("verification");
  state.step =
    initialStep >= 0 &&
    (state.loggedIn || initialPublicRoutes.includes(steps[initialStep].id))
      ? initialStep
      : 0;
  if (state.loggedIn && (initialStep < 0 || steps[state.step].id === "programs")) {
    state.dashboardView =
      initialStep >= 0 && steps[initialStep].id === "programs"
        ? "programs"
        : "home";
    state.step = steps.findIndex((step) => step.id === "member-programs");
  }
  render();
  const initialSection = window.location.hash.slice(1);
  if (
    !state.loggedIn &&
    ["facilities"].includes(initialSection)
  ) {
    window.setTimeout(() => {
      document
        .getElementById(initialSection)
        ?.scrollIntoView({ block: "start" });
    }, 50);
  }
  if (backend.flash?.error) showToast(backend.flash.error, "danger");
  if (backend.flash?.status) showToast(backend.flash.status, "success");
  if (state.loggedIn) loadCurrentApplication();
})();
