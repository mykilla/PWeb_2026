/* LibraryReg: JavaScript demo (tanpa backend).
   Semua data disimpan di localStorage browser ini saja. Login hanyalah simulasi. */

(function () {
  "use strict";

  const STORAGE_KEY = "libraryreg_members_v2";
  const SESSION_KEY = "libraryreg_staff_session";
  const DEMO_USERNAME = "petugas";
  const DEMO_PASSWORD = "library123";

  const SEED_MEMBERS = [
    { id: "LR-0001", name: "Aditya Pratama", memberId: "5026201001", memberType: "Mahasiswa", studyProgram: "Informatika", email: "aditya@example.com", phone: "0812-0000-0001", address: "Surabaya, Jawa Timur", status: "Terdaftar" },
    { id: "LR-0002", name: "Nadia Putri", memberId: "5026201002", memberType: "Mahasiswa", studyProgram: "Sistem Informasi", email: "nadia@example.com", phone: "0812-0000-0002", address: "Sidoarjo, Jawa Timur", status: "Terdaftar" },
    { id: "LR-0003", name: "Dr. Bima Santoso", memberId: "19870101001", memberType: "Dosen", studyProgram: "Fakultas Teknologi", email: "bima@example.com", phone: "0812-0000-0003", address: "Surabaya, Jawa Timur", status: "Terdaftar" }
  ];

  /* ---------- penyimpanan ---------- */
  function readMembers() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw === null) {
        saveMembers(SEED_MEMBERS);
        return SEED_MEMBERS.slice();
      }
      const saved = JSON.parse(raw);
      return Array.isArray(saved) ? saved : [];
    } catch (error) {
      return SEED_MEMBERS.slice();
    }
  }

  function saveMembers(members) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(members)); } catch (error) { /* diabaikan */ }
  }

  function isLoggedIn() {
    try { return localStorage.getItem(SESSION_KEY) === "true"; } catch (error) { return false; }
  }

  /* ---------- util ---------- */
  function showMessage(element, text, type) {
    if (!element) return;
    element.textContent = text;
    element.className = "form-message" + (type ? " " + type : "");
  }

  function field(form, name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function valueOf(form, name) {
    const el = field(form, name);
    return el && typeof el.value === "string" ? el.value.trim() : "";
  }

  function nextId(members) {
    const numbers = members.map(m => parseInt(String(m.id).replace(/\D/g, ""), 10) || 0);
    return "LR-" + String(Math.max(0, ...numbers) + 1).padStart(4, "0");
  }

  function initials(name) {
    const parts = String(name || "").replace(/^(dr|prof|ir)\.?\s+/i, "").trim().split(/\s+/).filter(Boolean);
    return ((parts[0] || "?")[0] + (parts.length > 1 ? parts[parts.length - 1][0] : "")).toUpperCase();
  }

  function memberIdFromUrl() {
    return new URLSearchParams(window.location.search).get("id");
  }

  /* Tanpa ?id= kita pakai anggota pertama, supaya tautan statis di dashboard tetap berfungsi. */
  function currentMember() {
    const members = readMembers();
    const id = memberIdFromUrl();
    return id ? members.find(m => m.id === id) : members[0];
  }

  /* ---------- akses & sesi ---------- */
  function guardPages() {
    if (document.body.hasAttribute("data-require-login") && !isLoggedIn()) {
      window.location.replace("login.html");
    }
  }

  function handleLogin() {
    const form = document.querySelector("#loginForm");
    if (!form) return;
    const message = form.querySelector(".form-message");
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      if (valueOf(form, "username") === DEMO_USERNAME && form.querySelector("#password").value === DEMO_PASSWORD) {
        try { localStorage.setItem(SESSION_KEY, "true"); } catch (error) { /* diabaikan */ }
        showMessage(message, "Login berhasil. Membuka dashboard…", "text-success");
        window.setTimeout(function () { window.location.href = "dashboard-petugas.html"; }, 500);
      } else {
        showMessage(message, "Nama pengguna atau kata sandi salah.", "text-danger");
      }
    });
  }

  function handleLogout() {
    document.querySelectorAll("[data-logout]").forEach(function (link) {
      link.addEventListener("click", function () {
        try { localStorage.removeItem(SESSION_KEY); } catch (error) { /* diabaikan */ }
      });
    });
  }

  /* ---------- pendaftaran ---------- */
  function handleRegistration() {
    const form = document.querySelector("#registrationForm");
    if (!form) return;
    const message = document.querySelector("#formMessage");
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const members = readMembers();
      const memberId = valueOf(form, "memberId");
      const email = valueOf(form, "email");
      if (members.some(m => m.memberId === memberId)) {
        showMessage(message, "NIM/NIP tersebut sudah terdaftar.", "text-danger");
        return;
      }
      if (members.some(m => String(m.email).toLowerCase() === email.toLowerCase())) {
        showMessage(message, "Email tersebut sudah terdaftar.", "text-danger");
        return;
      }
      members.push({
        id: nextId(members),
        name: valueOf(form, "fullName"),
        memberId: memberId,
        memberType: valueOf(form, "memberType"),
        studyProgram: valueOf(form, "studyProgram"),
        email: email,
        phone: valueOf(form, "phone"),
        address: valueOf(form, "address"),
        status: "Terdaftar"
      });
      saveMembers(members);
      form.reset();
      showMessage(message, "Pendaftaran berhasil disimpan di browser ini. Terima kasih!", "text-success");
    });
  }

  /* ---------- daftar anggota ---------- */
  function makeCell(row, text) {
    const cell = document.createElement("td");
    cell.textContent = text || "-";
    row.appendChild(cell);
    return cell;
  }

  function makeLink(text, href, className) {
    const a = document.createElement("a");
    a.textContent = text;
    a.href = href;
    a.className = className;
    return a;
  }

  function renderMemberTable() {
    const tableBody = document.querySelector("#memberTableBody");
    if (!tableBody) return;
    const search = document.querySelector("#memberSearch");
    const counters = {
      total: document.querySelector("#totalMembers"),
      students: document.querySelector("#totalStudents"),
      staff: document.querySelector("#totalStaff")
    };

    function updateSummary(all) {
      if (counters.total) counters.total.textContent = all.length;
      if (counters.students) counters.students.textContent = all.filter(m => m.memberType === "Mahasiswa").length;
      if (counters.staff) counters.staff.textContent = all.filter(m => m.memberType !== "Mahasiswa").length;
    }

    function draw() {
      const all = readMembers();
      updateSummary(all);
      const term = search ? search.value.trim().toLowerCase() : "";
      const rows = all.filter(m =>
        [m.name, m.memberId, m.memberType, m.studyProgram, m.email].some(v => String(v || "").toLowerCase().includes(term))
      );
      tableBody.replaceChildren();
      if (!rows.length) {
        const row = document.createElement("tr");
        const cell = makeCell(row, "Data anggota tidak ditemukan.");
        cell.colSpan = 6;
        tableBody.appendChild(row);
        return;
      }
      rows.forEach(function (m) {
        const row = document.createElement("tr");
        makeCell(row, m.name);
        makeCell(row, m.memberId);
        makeCell(row, m.memberType);
        makeCell(row, m.studyProgram);
        const statusCell = document.createElement("td");
        const pill = document.createElement("span");
        pill.className = "status-pill";
        pill.textContent = m.status || "Terdaftar";
        statusCell.appendChild(pill);
        row.appendChild(statusCell);

        const actions = document.createElement("td");
        actions.className = "table-actions";
        const q = "?id=" + encodeURIComponent(m.id);
        actions.appendChild(makeLink("Detail", "detail-anggota.html" + q, "table-link"));
        if (isLoggedIn()) {
          actions.appendChild(makeLink("Edit", "edit-anggota.html" + q, "table-link"));
          const remove = document.createElement("button");
          remove.type = "button";
          remove.className = "table-link table-link-danger";
          remove.textContent = "Hapus";
          remove.addEventListener("click", function () {
            if (!window.confirm("Hapus data anggota " + m.name + "?")) return;
            saveMembers(readMembers().filter(item => item.id !== m.id));
            draw();
          });
          actions.appendChild(remove);
        }
        row.appendChild(actions);
        tableBody.appendChild(row);
      });
    }

    if (search) search.addEventListener("input", draw);
    draw();
  }

  /* ---------- detail anggota ---------- */
  function fillMemberDetail() {
    const target = document.querySelector("[data-member-detail]");
    if (!target) return;
    const member = currentMember();
    if (!member) {
      target.replaceChildren();
      const p = document.createElement("p");
      p.textContent = "Data anggota tidak ditemukan. ";
      p.appendChild(makeLink("Kembali ke daftar anggota", "daftar-anggota.html", ""));
      target.appendChild(p);
      return;
    }
    const data = Object.assign({}, member, {
      initials: initials(member.name),
      summary: member.memberId + " · " + member.memberType
    });
    target.querySelectorAll("[data-field]").forEach(function (el) {
      el.textContent = data[el.getAttribute("data-field")] || "-";
    });
    document.querySelectorAll("[data-edit-link]").forEach(function (a) {
      a.href = "edit-anggota.html?id=" + encodeURIComponent(member.id);
      a.hidden = !isLoggedIn();
    });
  }

  /* ---------- edit anggota ---------- */
  function fillEditForm() {
    const form = document.querySelector("#editMemberForm");
    if (!form) return;
    const message = document.querySelector("#editMessage");
    const member = currentMember();
    if (!member) {
      showMessage(message, "Data anggota tidak ditemukan.", "text-danger");
      form.querySelectorAll("input, select, textarea, button").forEach(el => { el.disabled = true; });
      return;
    }
    document.querySelectorAll("[data-detail-link]").forEach(function (a) {
      a.href = "detail-anggota.html?id=" + encodeURIComponent(member.id);
    });
    ["fullName", "memberId", "memberType", "studyProgram", "email", "phone", "address"].forEach(function (name) {
      const el = field(form, name);
      const key = name === "fullName" ? "name" : name;
      if (el) el.value = member[key] || "";
    });
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const all = readMembers();
      const memberId = valueOf(form, "memberId");
      if (all.some(m => m.id !== member.id && m.memberId === memberId)) {
        showMessage(message, "NIM/NIP tersebut sudah dipakai anggota lain.", "text-danger");
        return;
      }
      const updated = Object.assign({}, member, {
        name: valueOf(form, "fullName"),
        memberId: memberId,
        memberType: valueOf(form, "memberType"),
        studyProgram: valueOf(form, "studyProgram"),
        email: valueOf(form, "email"),
        phone: valueOf(form, "phone"),
        address: valueOf(form, "address")
      });
      saveMembers(all.map(m => m.id === member.id ? updated : m));
      showMessage(message, "Perubahan berhasil disimpan.", "text-success");
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    guardPages();
    handleLogin();
    handleLogout();
    handleRegistration();
    renderMemberTable();
    fillMemberDetail();
    fillEditForm();
  });
})();
