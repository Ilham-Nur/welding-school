"use client";

import { FormEvent, useMemo, useState } from "react";

type View = "public" | "programs" | "student" | "admin";

type Program = {
  id: number;
  code: string;
  title: string;
  subtitle: string;
  duration: string;
  theory: string;
  practice: string;
  seats: number;
  price: string;
  level: "Pemula" | "Menengah" | "Lanjutan";
  position: string;
  popular?: boolean;
};

const programs: Program[] = [
  {
    id: 1,
    code: "SMAW",
    title: "SMAW Welder",
    subtitle: "Shielded Metal Arc Welding",
    duration: "80 Jam",
    theory: "20 Jam",
    practice: "60 Jam",
    seats: 12,
    price: "Rp 3.500.000",
    level: "Pemula",
    position: "68% center",
    popular: true,
  },
  {
    id: 2,
    code: "FCAW",
    title: "FCAW Welder",
    subtitle: "Flux Cored Arc Welding",
    duration: "80 Jam",
    theory: "20 Jam",
    practice: "60 Jam",
    seats: 12,
    price: "Rp 3.800.000",
    level: "Menengah",
    position: "79% center",
  },
  {
    id: 3,
    code: "GTAW",
    title: "GTAW Welder",
    subtitle: "Gas Tungsten Arc Welding",
    duration: "80 Jam",
    theory: "20 Jam",
    practice: "60 Jam",
    seats: 10,
    price: "Rp 4.000.000",
    level: "Lanjutan",
    position: "92% center",
  },
  {
    id: 4,
    code: "GMAW",
    title: "GMAW Welder",
    subtitle: "Gas Metal Arc Welding",
    duration: "80 Jam",
    theory: "20 Jam",
    practice: "60 Jam",
    seats: 12,
    price: "Rp 3.800.000",
    level: "Menengah",
    position: "84% center",
  },
  {
    id: 5,
    code: "WQT",
    title: "Welder Qualification Test",
    subtitle: "Uji Kompetensi Welder",
    duration: "16 Jam",
    theory: "4 Jam",
    practice: "12 Jam",
    seats: 8,
    price: "Rp 2.000.000",
    level: "Lanjutan",
    position: "72% center",
  },
  {
    id: 6,
    code: "WID",
    title: "Welding Inspector Dasar",
    subtitle: "Basic Welding Inspector",
    duration: "40 Jam",
    theory: "20 Jam",
    practice: "20 Jam",
    seats: 12,
    price: "Rp 3.000.000",
    level: "Pemula",
    position: "88% center",
  },
];

const publicStats = [
  { icon: "◎", value: "2.458", label: "Alumni Bersertifikat", tone: "orange" },
  { icon: "◇", value: "1.126", label: "Peserta Aktif", tone: "blue" },
  { icon: "▣", value: "1.872", label: "Alumni Bekerja", tone: "green" },
  { icon: "◷", value: "134", label: "Siap Ditempatkan", tone: "slate" },
];

const dashboardMenu = [
  ["⌂", "Dashboard"],
  ["◉", "Materi"],
  ["▤", "Tugas"],
  ["□", "Jadwal"],
  ["✓", "Kehadiran"],
  ["▱", "Logbook"],
  ["◇", "Kompetensi"],
  ["◎", "Nilai"],
  ["▣", "Sertifikat"],
  ["▰", "Status Pekerjaan"],
];

const adminMenu = [
  ["⌂", "Dashboard"],
  ["◉", "Peserta"],
  ["◎", "Alumni"],
  ["▣", "Pelatihan"],
  ["▤", "Silabus"],
  ["✓", "Penilaian"],
  ["◇", "Sertifikat"],
  ["▰", "Penempatan Kerja"],
  ["▱", "Keuangan"],
  ["□", "Inventaris"],
];

function Brand({ compact = false }: { compact?: boolean }) {
  return (
    <div className={`brand ${compact ? "brand--compact" : ""}`}>
      <span className="brand__mark" aria-hidden="true">
        <span>W</span>
      </span>
      <span className="brand__copy">
        <strong>WELDING SCHOOL</strong>
        <small>MANAGEMENT SYSTEM</small>
      </span>
    </div>
  );
}

function TemplateSwitcher({
  view,
  onChange,
}: {
  view: View;
  onChange: (view: View) => void;
}) {
  const options: { value: View; label: string }[] = [
    { value: "public", label: "Beranda" },
    { value: "programs", label: "Program" },
    { value: "student", label: "Peserta" },
    { value: "admin", label: "Admin" },
  ];

  return (
    <div className="template-switcher" aria-label="Pilih contoh template">
      <span className="template-switcher__label">Preview</span>
      {options.map((option) => (
        <button
          className={view === option.value ? "is-active" : ""}
          key={option.value}
          onClick={() => onChange(option.value)}
          type="button"
        >
          {option.label}
        </button>
      ))}
    </div>
  );
}

function PublicHeader({
  active,
  onChange,
}: {
  active: View;
  onChange: (view: View) => void;
}) {
  const [menuOpen, setMenuOpen] = useState(false);
  const links = [
    ["Beranda", "public"],
    ["Program", "programs"],
    ["Aktivitas", "public"],
    ["Verifikasi Sertifikat", "public"],
  ] as const;

  return (
    <header className="public-header">
      <a className="brand-link" href="#top" aria-label="Welding School beranda">
        <Brand />
      </a>
      <button
        className="menu-toggle"
        aria-expanded={menuOpen}
        aria-label="Buka menu"
        onClick={() => setMenuOpen((current) => !current)}
        type="button"
      >
        {menuOpen ? "×" : "☰"}
      </button>
      <nav className={menuOpen ? "is-open" : ""} aria-label="Navigasi utama">
        {links.map(([label, target]) => (
          <button
            className={
              active === target && (label === "Beranda" || label === "Program")
                ? "is-active"
                : ""
            }
            key={label}
            onClick={() => {
              onChange(target);
              setMenuOpen(false);
              if (label.includes("Verifikasi")) {
                setTimeout(
                  () =>
                    document
                      .getElementById("verify")
                      ?.scrollIntoView({ behavior: "smooth" }),
                  50,
                );
              }
            }}
            type="button"
          >
            {label}
          </button>
        ))}
      </nav>
      <button
        className="button button--outline-light header-login"
        onClick={() => onChange("student")}
        type="button"
      >
        <span aria-hidden="true">○</span> Login
      </button>
    </header>
  );
}

function ProgramCard({
  program,
  onDetail,
}: {
  program: Program;
  onDetail: (program: Program) => void;
}) {
  return (
    <article className="program-card">
      <div
        className="program-card__media"
        style={{ backgroundPosition: program.position }}
      >
        {program.popular && <span className="badge badge--orange">Populer</span>}
        <span className="program-card__code">{program.code}</span>
      </div>
      <div className="program-card__body">
        <div>
          <h3>{program.title}</h3>
          <p>{program.subtitle}</p>
        </div>
        <dl className="program-meta">
          <div>
            <dt>Durasi</dt>
            <dd>{program.duration}</dd>
          </div>
          <div>
            <dt>Praktik</dt>
            <dd>{program.practice}</dd>
          </div>
          <div>
            <dt>Kuota</dt>
            <dd>{program.seats} Peserta</dd>
          </div>
          <div>
            <dt>Biaya</dt>
            <dd>{program.price}</dd>
          </div>
        </dl>
        <div className="program-card__footer">
          <button
            className="button button--ghost"
            onClick={() => onDetail(program)}
            type="button"
          >
            Detail
          </button>
          <button
            className="button button--primary"
            onClick={() => onDetail(program)}
            type="button"
          >
            Daftar <span aria-hidden="true">→</span>
          </button>
        </div>
      </div>
    </article>
  );
}

function PublicHome({
  onChange,
  onDetail,
  notify,
}: {
  onChange: (view: View) => void;
  onDetail: (program: Program) => void;
  notify: (message: string) => void;
}) {
  const [certificate, setCertificate] = useState("WSC-SMAW-2026-0001");
  const [verified, setVerified] = useState(false);

  const submitVerification = (event: FormEvent) => {
    event.preventDefault();
    if (!certificate.trim()) {
      notify("Masukkan nomor sertifikat terlebih dahulu.");
      return;
    }
    setVerified(true);
    notify("Sertifikat ditemukan dan masih aktif.");
  };

  return (
    <div className="public-site" id="top">
      <PublicHeader active="public" onChange={onChange} />
      <main>
        <section className="hero">
          <div className="hero__content page-shell">
            <div className="eyebrow eyebrow--light">
              PUSAT PELATIHAN &amp; SERTIFIKASI WELDER
            </div>
            <h1>
              Membangun Welder Profesional,
              <br />
              Kompeten dan <span>Siap Kerja</span>
            </h1>
            <p>
              Program pelatihan pengelasan berbasis industri dengan instruktur
              berpengalaman, workshop modern, dan sertifikasi yang diakui.
            </p>
            <div className="hero__actions">
              <button
                className="button button--primary button--large"
                onClick={() => onChange("programs")}
                type="button"
              >
                Jelajahi Program <span aria-hidden="true">→</span>
              </button>
              <button
                className="button button--glass button--large"
                onClick={() =>
                  document
                    .getElementById("verify")
                    ?.scrollIntoView({ behavior: "smooth" })
                }
                type="button"
              >
                Verifikasi Sertifikat
              </button>
            </div>
          </div>
          <div className="hero__trust">
            <span>Terakreditasi</span>
            <strong>BNSP</strong>
            <i />
            <strong>KEMNAKER</strong>
          </div>
        </section>

        <section className="stats-strip page-shell" aria-label="Statistik utama">
          {publicStats.map((stat) => (
            <article className="stat-card" key={stat.label}>
              <span className={`stat-icon stat-icon--${stat.tone}`}>
                {stat.icon}
              </span>
              <div>
                <strong>{stat.value}</strong>
                <span>{stat.label}</span>
              </div>
            </article>
          ))}
        </section>

        <section className="content-section page-shell">
          <div className="section-heading">
            <div>
              <span className="eyebrow">PROGRAM UNGGULAN</span>
              <h2>Pilih keahlian untuk masa depan Anda</h2>
              <p>
                Kurikulum praktis yang disusun bersama kebutuhan industri.
              </p>
            </div>
            <button
              className="text-link"
              onClick={() => onChange("programs")}
              type="button"
            >
              Lihat semua program <span aria-hidden="true">→</span>
            </button>
          </div>
          <div className="program-grid program-grid--featured">
            {programs.slice(0, 3).map((program) => (
              <ProgramCard
                key={program.id}
                onDetail={onDetail}
                program={program}
              />
            ))}
          </div>
        </section>

        <section className="feature-band">
          <div className="page-shell feature-band__inner">
            <div className="feature-band__copy">
              <span className="eyebrow">KENAPA WELDING SCHOOL</span>
              <h2>Belajar di workshop. Tumbuh bersama industri.</h2>
              <p>
                Setiap peserta dibimbing dari fondasi keselamatan hingga
                kompetensi kerja yang terukur.
              </p>
              <button
                className="button button--primary"
                onClick={() => onChange("programs")}
                type="button"
              >
                Lihat fasilitas <span aria-hidden="true">↗</span>
              </button>
            </div>
            <div className="feature-list">
              {[
                ["01", "Instruktur Industri", "Praktisi tersertifikasi dan aktif."],
                ["02", "Workshop Modern", "Peralatan lengkap dan terkalibrasi."],
                ["03", "Sertifikasi Resmi", "Skema kompetensi berstandar nasional."],
                ["04", "Career Support", "Akses jejaring mitra dan alumni."],
              ].map(([number, title, text]) => (
                <article key={number}>
                  <span>{number}</span>
                  <div>
                    <h3>{title}</h3>
                    <p>{text}</p>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className="content-section page-shell">
          <div className="section-heading">
            <div>
              <span className="eyebrow">AKTIVITAS TERBARU</span>
              <h2>Kabar dari workshop</h2>
            </div>
            <button className="text-link" type="button">
              Lihat semua <span aria-hidden="true">→</span>
            </button>
          </div>
          <div className="activity-grid">
            {[
              ["24 Mei 2026", "Pembukaan Kelas SMAW Batch 26-05", "Kelas"],
              ["20 Mei 2026", "Uji Kompetensi Welder Mitra Industri", "Sertifikasi"],
              ["15 Mei 2026", "Kunjungan Industri PT. Pupuk Kaltim", "Industri"],
            ].map(([date, title, category], index) => (
              <article className="activity-card" key={title}>
                <div
                  className="activity-card__media"
                  style={{ backgroundPosition: `${64 + index * 12}% center` }}
                >
                  <span>{category}</span>
                </div>
                <div className="activity-card__body">
                  <time>{date}</time>
                  <h3>{title}</h3>
                  <a href="#verify">Baca cerita <span aria-hidden="true">→</span></a>
                </div>
              </article>
            ))}
          </div>
        </section>

        <section className="verify-section" id="verify">
          <div className="page-shell">
            <div className="verify-section__intro">
              <span className="eyebrow eyebrow--light">VERIFIKASI SERTIFIKAT</span>
              <h2>Pastikan keaslian sertifikat secara resmi</h2>
              <p>
                Masukkan nomor sertifikat untuk melihat status dan detail
                kompetensi pemegangnya.
              </p>
            </div>
            <form className="verify-box" onSubmit={submitVerification}>
              <label htmlFor="certificate">Nomor Sertifikat</label>
              <div className="verify-box__input">
                <input
                  id="certificate"
                  onChange={(event) => {
                    setCertificate(event.target.value);
                    setVerified(false);
                  }}
                  placeholder="Contoh: WSC-SMAW-2026-0001"
                  value={certificate}
                />
                <button className="button button--primary" type="submit">
                  Verifikasi <span aria-hidden="true">→</span>
                </button>
              </div>
              {verified && (
                <div className="verification-result" aria-live="polite">
                  <span className="verification-result__check">✓</span>
                  <div>
                    <small>HASIL VERIFIKASI</small>
                    <strong>Valid &amp; Aktif</strong>
                    <p>
                      Rizky A. Pratama · SMAW 1G (Flat) · Berlaku hingga 24 Mei
                      2029
                    </p>
                  </div>
                  <span className="badge badge--green">TERVERIFIKASI</span>
                </div>
              )}
            </form>
          </div>
        </section>
      </main>
      <PublicFooter onChange={onChange} />
    </div>
  );
}

function PublicFooter({ onChange }: { onChange: (view: View) => void }) {
  return (
    <footer className="public-footer">
      <div className="page-shell public-footer__grid">
        <div>
          <Brand />
          <p>
            Membangun welder profesional melalui pelatihan berkualitas dan
            sertifikasi berstandar nasional.
          </p>
        </div>
        <div>
          <h3>Menu</h3>
          <button onClick={() => onChange("public")} type="button">Beranda</button>
          <button onClick={() => onChange("programs")} type="button">Program</button>
          <button type="button">Aktivitas</button>
          <button type="button">Tentang Kami</button>
        </div>
        <div>
          <h3>Layanan</h3>
          <button type="button">Verifikasi Sertifikat</button>
          <button type="button">Pendaftaran</button>
          <button type="button">FAQ</button>
          <button type="button">Kontak</button>
        </div>
        <div>
          <h3>Kontak Kami</h3>
          <p>Jl. Industri No. 88, Kawasan Industri Cilegon, Banten 42435</p>
          <p>0254-123456</p>
          <p>info@weldingschool.id</p>
        </div>
      </div>
      <div className="public-footer__bottom">
        © 2026 Welding School Management System. All rights reserved.
      </div>
    </footer>
  );
}

function ProgramsPage({
  onChange,
  onDetail,
}: {
  onChange: (view: View) => void;
  onDetail: (program: Program) => void;
}) {
  const [query, setQuery] = useState("");
  const [level, setLevel] = useState("Semua");

  const filtered = useMemo(
    () =>
      programs.filter((program) => {
        const matchesQuery = `${program.title} ${program.subtitle} ${program.code}`
          .toLowerCase()
          .includes(query.toLowerCase());
        return matchesQuery && (level === "Semua" || program.level === level);
      }),
    [query, level],
  );

  return (
    <div className="public-site page-muted">
      <PublicHeader active="programs" onChange={onChange} />
      <main>
        <section className="page-title page-shell">
          <span className="eyebrow">PROGRAM PELATIHAN</span>
          <h1>Temukan program yang tepat</h1>
          <p>
            Pilih proses dan level keahlian sesuai tujuan karier Anda.
          </p>
        </section>
        <section className="program-browser page-shell">
          <div className="filter-bar">
            <label className="search-control">
              <span aria-hidden="true">⌕</span>
              <input
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Cari nama atau proses pelatihan..."
                value={query}
              />
            </label>
            <label className="select-control">
              <span>Level</span>
              <select
                onChange={(event) => setLevel(event.target.value)}
                value={level}
              >
                <option>Semua</option>
                <option>Pemula</option>
                <option>Menengah</option>
                <option>Lanjutan</option>
              </select>
            </label>
            <label className="select-control">
              <span>Durasi</span>
              <select defaultValue="Semua">
                <option>Semua</option>
                <option>≤ 40 Jam</option>
                <option>80 Jam</option>
              </select>
            </label>
          </div>
          <div className="browser-summary">
            <p>
              Menampilkan <strong>{filtered.length}</strong> program tersedia
            </p>
            {(query || level !== "Semua") && (
              <button
                className="text-link"
                onClick={() => {
                  setQuery("");
                  setLevel("Semua");
                }}
                type="button"
              >
                Reset filter
              </button>
            )}
          </div>
          <div className="program-grid">
            {filtered.map((program) => (
              <ProgramCard
                key={program.id}
                onDetail={onDetail}
                program={program}
              />
            ))}
          </div>
          {filtered.length === 0 && (
            <div className="empty-state">
              <span>⌕</span>
              <h2>Program belum ditemukan</h2>
              <p>Coba kata kunci atau level pelatihan yang lain.</p>
            </div>
          )}
        </section>
      </main>
      <PublicFooter onChange={onChange} />
    </div>
  );
}

function ProgramDrawer({
  program,
  onClose,
  notify,
}: {
  program: Program;
  onClose: () => void;
  notify: (message: string) => void;
}) {
  return (
    <div className="drawer-backdrop" onMouseDown={onClose}>
      <aside
        aria-label={`Detail ${program.title}`}
        aria-modal="true"
        className="program-drawer"
        onMouseDown={(event) => event.stopPropagation()}
        role="dialog"
      >
        <button
          aria-label="Tutup detail"
          className="drawer-close"
          onClick={onClose}
          type="button"
        >
          ×
        </button>
        <div
          className="program-drawer__hero"
          style={{ backgroundPosition: program.position }}
        >
          <span className="badge badge--orange">{program.code}</span>
        </div>
        <div className="program-drawer__content">
          <span className="eyebrow">PROGRAM DETAIL</span>
          <h2>{program.title}</h2>
          <p className="lead">{program.subtitle}</p>
          <div className="drawer-facts">
            <div><span>Durasi</span><strong>{program.duration}</strong></div>
            <div><span>Teori</span><strong>{program.theory}</strong></div>
            <div><span>Praktik</span><strong>{program.practice}</strong></div>
            <div><span>Kuota</span><strong>{program.seats} peserta</strong></div>
          </div>
          <h3>Yang akan Anda pelajari</h3>
          <ul className="check-list">
            <li>Prinsip dasar dan keselamatan kerja pengelasan</li>
            <li>Persiapan material, mesin, dan alat pelindung diri</li>
            <li>Teknik pengelasan posisi 1G, 2G, 3G, dan 4G</li>
            <li>Inspeksi visual dan perbaikan cacat las</li>
          </ul>
          <div className="drawer-price">
            <div><span>Biaya Program</span><strong>{program.price}</strong></div>
            <button
              className="button button--primary button--large"
              onClick={() => {
                notify(`Pendaftaran ${program.title} dimulai.`);
                onClose();
              }}
              type="button"
            >
              Daftar Sekarang <span aria-hidden="true">→</span>
            </button>
          </div>
        </div>
      </aside>
    </div>
  );
}

function DashboardShell({
  admin = false,
  children,
  onChange,
}: {
  admin?: boolean;
  children: React.ReactNode;
  onChange: (view: View) => void;
}) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const menu = admin ? adminMenu : dashboardMenu;

  return (
    <div className={`dashboard-shell ${sidebarOpen ? "sidebar-open" : ""}`}>
      <aside className="sidebar">
        <Brand />
        {!admin && (
          <div className="sidebar-profile">
            <span className="avatar">RA</span>
            <div>
              <strong>Rizky A. Pratama</strong>
              <span>Alumni · Online</span>
            </div>
          </div>
        )}
        <nav aria-label="Menu dashboard">
          {menu.map(([icon, label], index) => (
            <button className={index === 0 ? "is-active" : ""} key={label} type="button">
              <span aria-hidden="true">{icon}</span>
              {label}
              {admin && [1, 2, 3, 5].includes(index) && <i>›</i>}
            </button>
          ))}
        </nav>
        <button className="sidebar-exit" onClick={() => onChange("public")} type="button">
          <span aria-hidden="true">↗</span> Kembali ke website
        </button>
      </aside>
      <button
        aria-label="Tutup menu"
        className="sidebar-scrim"
        onClick={() => setSidebarOpen(false)}
        type="button"
      />
      <div className="dashboard-main">
        <header className="dashboard-topbar">
          <button
            aria-label="Buka menu"
            className="dashboard-menu-button"
            onClick={() => setSidebarOpen(true)}
            type="button"
          >
            ☰
          </button>
          <strong>Dashboard</strong>
          <div className="dashboard-topbar__profile">
            <button className="notification-button" type="button">
              ♢<span>{admin ? 8 : 3}</span>
            </button>
            <span className="avatar avatar--small">{admin ? "AU" : "RA"}</span>
            <div>
              <strong>{admin ? "Admin Utama" : "Rizky A. Pratama"}</strong>
              <span>{admin ? "Super Admin" : "Alumni"}</span>
            </div>
          </div>
        </header>
        <main className="dashboard-content">{children}</main>
        <footer className="dashboard-footer">
          © 2026 Welding School Management System.
        </footer>
      </div>
    </div>
  );
}

function MetricCard({
  icon,
  label,
  value,
  detail,
  tone,
}: {
  icon: string;
  label: string;
  value: string;
  detail: string;
  tone: string;
}) {
  return (
    <article className="metric-card">
      <span className={`metric-card__icon metric-card__icon--${tone}`}>{icon}</span>
      <div>
        <span>{label}</span>
        <strong>{value}</strong>
        <small>{detail}</small>
      </div>
    </article>
  );
}

function StudentDashboard({ onChange }: { onChange: (view: View) => void }) {
  return (
    <DashboardShell onChange={onChange}>
      <section className="welcome-banner">
        <div>
          <span>Selamat datang kembali,</span>
          <h1>Rizky A. Pratama</h1>
          <div className="welcome-banner__facts">
            <div><span>Program</span><strong>SMAW (Stick)</strong></div>
            <div><span>Batch</span><strong>Mei 2026</strong></div>
            <div><span>Instruktur</span><strong>Pak Dedi Setiawan</strong></div>
          </div>
        </div>
      </section>
      <section className="metric-grid metric-grid--student">
        <MetricCard icon="◎" label="Progress Pelatihan" value="72%" detail="18 dari 25 materi" tone="green" />
        <MetricCard icon="✓" label="Kehadiran" value="93%" detail="28 dari 30 sesi" tone="blue" />
        <MetricCard icon="▤" label="Tugas Belum Selesai" value="2" detail="Dari 8 tugas" tone="orange" />
        <MetricCard icon="◷" label="Jam Praktik" value="48,5" detail="Dari 64 jam" tone="green" />
      </section>
      <section className="dashboard-grid">
        <article className="panel schedule-panel">
          <div className="panel-heading"><h2>Jadwal Berikutnya</h2><button type="button">Lihat semua →</button></div>
          <div className="schedule-item">
            <div className="date-card"><strong>24</strong><span>MEI</span></div>
            <div>
              <span className="badge badge--blue">Praktik</span>
              <h3>Praktik SMAW (1G) Posisi Flat</h3>
              <p>08.00–12.00 WIB · Workshop Cilegon Area 1</p>
            </div>
          </div>
          <div className="schedule-item schedule-item--small">
            <div className="date-dot" />
            <div>
              <span className="badge badge--green">Teori</span>
              <h3>Cacat Las &amp; Pencegahannya</h3>
              <p>Senin, 26 Mei · Ruang Kelas 2</p>
            </div>
          </div>
        </article>
        <article className="panel progress-panel">
          <div className="panel-heading"><h2>Progress Materi</h2><button type="button">Lihat semua →</button></div>
          <div className="progress-panel__content">
            <div className="donut"><span><strong>72%</strong>Selesai</span></div>
            <div className="legend">
              <div><i className="green" /><span>Selesai</span><strong>18 (72%)</strong></div>
              <div><i className="blue" /><span>Berlangsung</span><strong>4 (16%)</strong></div>
              <div><i className="gray" /><span>Belum Mulai</span><strong>3 (12%)</strong></div>
            </div>
          </div>
        </article>
        <article className="panel task-panel">
          <div className="panel-heading"><h2>Daftar Tugas</h2><button type="button">Lihat semua →</button></div>
          {[
            ["PROSES", "Laporan Praktik 1G - Flat", "25 Mei"],
            ["PROSES", "Kuis Keselamatan Kerja", "26 Mei"],
            ["SELESAI", "Tugas Teori: Jenis-Jenis Las", "Nilai 92"],
            ["SELESAI", "Praktik Oxy-Acetylene", "Nilai 88"],
          ].map(([status, title, date]) => (
            <div className="task-row" key={title}>
              <span className={`badge ${status === "SELESAI" ? "badge--green" : "badge--orange-soft"}`}>{status}</span>
              <strong>{title}</strong>
              <small>{date}</small>
            </div>
          ))}
        </article>
        <article className="panel competency-panel">
          <div className="panel-heading"><h2>Matriks Kompetensi</h2><button type="button">Lihat detail →</button></div>
          {[
            ["SMAW 1G (Flat)", 90, "Kompeten"],
            ["SMAW 2F (Horizontal)", 75, "Hampir Kompeten"],
            ["SMAW 3G (Vertical Up)", 60, "Belum Kompeten"],
            ["Oxy-Acetylene Cutting", 85, "Kompeten"],
          ].map(([label, value, status]) => (
            <div className="competency-row" key={String(label)}>
              <span>{label}</span>
              <div className="mini-progress"><i style={{ width: `${value}%` }} /></div>
              <strong>{value}%</strong>
              <span className={`badge ${status === "Kompeten" ? "badge--green" : status === "Hampir Kompeten" ? "badge--orange-soft" : "badge--red"}`}>{status}</span>
            </div>
          ))}
        </article>
        <article className="panel logbook-panel">
          <div className="panel-heading"><h2>Logbook Terbaru</h2><button type="button">Lihat semua →</button></div>
          {[
            ["SMAW 1G - Flat", "4,0 Jam"],
            ["SMAW 2F - Horizontal", "4,0 Jam"],
            ["Oxy-Acetylene Cutting", "3,5 Jam"],
          ].map(([label, hours]) => (
            <div className="logbook-row" key={label}>
              <span className="logbook-thumb" />
              <div><strong>{label}</strong><small>20 Mei 2026 · {hours}</small></div>
              <span className="badge badge--green">Disetujui</span>
            </div>
          ))}
        </article>
      </section>
    </DashboardShell>
  );
}

function MiniBars() {
  const values = [42, 54, 68, 82, 89, 65, 55, 50, 63, 69, 74, 62];
  return (
    <div className="bar-chart" aria-label="Grafik peserta per bulan">
      {values.map((value, index) => (
        <div className="bar-chart__item" key={index}>
          <div className="bar-chart__bars">
            <i style={{ height: `${value}%` }} />
            <b style={{ height: `${Math.max(value - 25, 18)}%` }} />
          </div>
          <span>{["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"][index]}</span>
        </div>
      ))}
    </div>
  );
}

function AdminDashboard({
  onChange,
  notify,
}: {
  onChange: (view: View) => void;
  notify: (message: string) => void;
}) {
  const adminMetrics = [
    ["◉", "Peserta Aktif", "1.126", "↑ 8,4% dari bulan lalu", "blue"],
    ["◇", "Alumni", "2.458", "↑ 9,2% dari bulan lalu", "navy"],
    ["▣", "Batch Berjalan", "12", "Sama seperti bulan lalu", "orange"],
    ["▰", "Alumni Bekerja", "1.872", "↑ 11,7% dari bulan lalu", "green"],
    ["◷", "Standby", "134", "↓ 3,6% dari bulan lalu", "slate"],
    ["▱", "Pendapatan Bulan Ini", "Rp 985,7 jt", "↑ 16,3% dari bulan lalu", "green"],
  ];

  return (
    <DashboardShell admin onChange={onChange}>
      <section className="admin-metric-grid">
        {adminMetrics.map(([icon, label, value, detail, tone]) => (
          <MetricCard key={label} icon={icon} label={label} value={value} detail={detail} tone={tone} />
        ))}
      </section>
      <section className="admin-layout">
        <div className="admin-main-column">
          <div className="analytics-grid">
            <article className="panel analytics-panel">
              <div className="panel-heading"><h2>Peserta per Bulan</h2><select defaultValue="2026"><option>2026</option><option>2025</option></select></div>
              <div className="chart-legend"><span><i className="light-blue" />Pendaftar</span><span><i className="navy" />Peserta Aktif</span></div>
              <MiniBars />
            </article>
            <article className="panel analytics-panel">
              <div className="panel-heading"><h2>Keuangan Tahun Ini</h2><select defaultValue="2026"><option>2026</option><option>2025</option></select></div>
              <div className="chart-legend"><span><i className="green" />Pendapatan</span><span><i className="orange" />Pengeluaran</span></div>
              <div className="line-chart">
                <div className="line-chart__grid" />
                <div className="line-chart__income">●—●—●—●—●—●—●—●</div>
                <div className="line-chart__expense">●—●—●—●—●—●—●—●</div>
                <div className="line-chart__months">Jan&nbsp;&nbsp; Mar&nbsp;&nbsp; Mei&nbsp;&nbsp; Jul&nbsp;&nbsp; Sep&nbsp;&nbsp; Nov</div>
              </div>
            </article>
          </div>
          <div className="tables-grid">
            <DataPanel
              columns={["Nama", "Program", "Tgl Daftar"]}
              rows={[
                ["Ahmad Fauzan", "SMAW (Stick)", "24 Mei 2026"],
                ["Rizky Maulana", "GTAW (TIG)", "24 Mei 2026"],
                ["Dwi Setiawan", "FCAW", "23 Mei 2026"],
                ["Budi Santoso", "Welder Test", "23 Mei 2026"],
              ]}
              title="Pendaftaran Terbaru"
            />
            <DataPanel
              columns={["Tugas", "Batch", "Tgl"]}
              rows={[
                ["Laporan Praktik GTAW", "24-05", "24 Mei"],
                ["Ujian Teori FCAW", "24-04", "24 Mei"],
                ["WPS Test SMAW", "24-03", "23 Mei"],
                ["Laporan Praktik FCAW", "24-02", "23 Mei"],
              ]}
              title="Menunggu Pemeriksaan"
            />
            <DataPanel
              columns={["No. Sertifikat", "Nama", "Sisa"]}
              rows={[
                ["WS-23-0456", "Agus Setiawan", "24 hari"],
                ["WS-23-0512", "Fajar Ramadhan", "27 hari"],
                ["WS-23-0321", "Taufik Hidayat", "31 hari"],
                ["WS-23-0448", "M. Iqbal", "34 hari"],
              ]}
              title="Sertifikat Kedaluwarsa"
            />
          </div>
        </div>
        <aside className="admin-rail">
          <article className="panel quick-actions">
            <div className="panel-heading"><h2>Aksi Cepat</h2></div>
            {[
              ["▣", "Buat Sertifikat", "Buat sertifikat baru"],
              ["◉", "Tambah Peserta", "Daftarkan peserta baru"],
              ["▱", "Input Pembayaran", "Catat pembayaran"],
              ["▤", "Review Dokumen", "Periksa dokumen ISO"],
            ].map(([icon, title, text]) => (
              <button key={title} onClick={() => notify(`${title} dibuka.`)} type="button">
                <span>{icon}</span><div><strong>{title}</strong><small>{text}</small></div><i>→</i>
              </button>
            ))}
          </article>
          <article className="panel reminders">
            <div className="panel-heading"><h2>Pengingat Penting</h2><button type="button">Semua →</button></div>
            {[
              ["46", "sertifikat akan kedaluwarsa", "red"],
              ["18", "consumable segera menipis", "orange"],
              ["3", "dokumen ISO perlu review", "purple"],
              ["7", "tender mendekati deadline", "blue"],
            ].map(([value, label, tone]) => (
              <div className="reminder-row" key={label}>
                <span className={`reminder-icon reminder-icon--${tone}`}>{value}</span>
                <div><strong>{value} {label}</strong><button type="button">Lihat detail</button></div>
              </div>
            ))}
          </article>
        </aside>
      </section>
    </DashboardShell>
  );
}

function DataPanel({
  title,
  columns,
  rows,
}: {
  title: string;
  columns: string[];
  rows: string[][];
}) {
  return (
    <article className="panel data-panel">
      <div className="panel-heading"><h2>{title}</h2><button type="button">Lihat semua →</button></div>
      <div className="table-wrap">
        <table>
          <thead><tr>{columns.map((column) => <th key={column}>{column}</th>)}</tr></thead>
          <tbody>{rows.map((row) => <tr key={row.join("-")}>{row.map((cell, index) => <td key={cell} className={index === row.length - 1 && title.includes("Kedaluwarsa") ? "danger-cell" : ""}>{cell}</td>)}</tr>)}</tbody>
        </table>
      </div>
    </article>
  );
}

export default function Home() {
  const [view, setView] = useState<View>("public");
  const [selectedProgram, setSelectedProgram] = useState<Program | null>(null);
  const [toast, setToast] = useState("");

  const changeView = (nextView: View) => {
    setView(nextView);
    setSelectedProgram(null);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const notify = (message: string) => {
    setToast(message);
    window.setTimeout(() => setToast(""), 3200);
  };

  return (
    <>
      {view === "public" && (
        <PublicHome
          notify={notify}
          onChange={changeView}
          onDetail={setSelectedProgram}
        />
      )}
      {view === "programs" && (
        <ProgramsPage onChange={changeView} onDetail={setSelectedProgram} />
      )}
      {view === "student" && <StudentDashboard onChange={changeView} />}
      {view === "admin" && (
        <AdminDashboard notify={notify} onChange={changeView} />
      )}
      <TemplateSwitcher onChange={changeView} view={view} />
      {selectedProgram && (
        <ProgramDrawer
          notify={notify}
          onClose={() => setSelectedProgram(null)}
          program={selectedProgram}
        />
      )}
      {toast && <div className="toast" role="status">✓ {toast}</div>}
    </>
  );
}
