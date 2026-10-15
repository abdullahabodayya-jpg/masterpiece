const $ = (s) => document.querySelector(s);
const P = [
  ["Maya Chen", "#FF6B00"],
  ["Jordan Ellis", "#3B82F6"],
  ["Sofia Reyes", "#8B5CF6"],
  ["Omar Haddad", "#10B981"],
  ["Lena Novak", "#EC4899"],
  ["Tariq Aziz", "#F59E0B"],
];
const ED = [
  {
    n: "Sofia Reyes",
    s: "YouTube Editor",
    r: 4.9,
    p: 86,
    pr: "$120",
    loc: "Madrid",
    v: 1,
  },
  {
    n: "Omar Haddad",
    s: "Motion Designer",
    r: 4.8,
    p: 54,
    pr: "$200",
    loc: "Amman",
    v: 1,
  },
  {
    n: "Lena Novak",
    s: "Short-form Editor",
    r: 5.0,
    p: 112,
    pr: "$60",
    loc: "Prague",
    v: 1,
  },
  {
    n: "Tariq Aziz",
    s: "Podcast Editor",
    r: 4.7,
    p: 31,
    pr: "$80",
    loc: "Dubai",
    v: 0,
  },
];
const JOBS = [
  {
    t: "YouTube Series — Editor for 8 Episodes",
    c: "Jordan Ellis",
    b: "$1,800",
    d: "Oct 30",
    sw: "Premiere Pro",
    a: 14,
    ago: "2h",
    x: "Long-form",
  },
  {
    t: "Weekly TikTok Shorts Package",
    c: "Maya Chen",
    b: "$600/mo",
    d: "Ongoing",
    sw: "CapCut, After Effects",
    a: 31,
    ago: "5h",
    x: "Short-form",
  },
  {
    t: "Podcast Video Clips + Captions",
    c: "Studio Loop",
    b: "$450",
    d: "Oct 18",
    sw: "DaVinci Resolve",
    a: 9,
    ago: "1d",
    x: "Podcast",
  },
  {
    t: "Gaming Channel Intro Motion Pack",
    c: "Nova Plays",
    b: "$900",
    d: "Nov 5",
    sw: "After Effects",
    a: 22,
    ago: "2d",
    x: "Motion",
  },
];
const POSTS = [
  [
    "Maya Chen",
    "How do you structure your YouTube workflow? I batch scripts on Monday, film Tuesday, hand off to my editor Wednesday.",
    "YouTube",
    128,
    42,
  ],
  [
    "Sofia Reyes",
    "Just wrapped a 12-min documentary edit. Sound design changed everything — here is my breakdown.",
    "Video",
    256,
    63,
  ],
  [
    "Jordan Ellis",
    "Looking for feedback on this reel — is the first 3 seconds strong enough to hold attention?",
    "Social Media",
    89,
    37,
  ],
  [
    "Lena Novak",
    "What is your favorite editing workflow for fast-turnaround Reels?",
    "Freelancing",
    174,
    91,
  ],
];
const G = [
  "linear-gradient(135deg,#FF6B00,#7c2d00)",
  "linear-gradient(135deg,#3B82F6,#111)",
  "linear-gradient(135deg,#8B5CF6,#1e1b4b)",
  "linear-gradient(135deg,#10B981,#064e3b)",
  "linear-gradient(135deg,#EC4899,#500724)",
  "linear-gradient(135deg,#F59E0B,#451a03)",
];
let S = {
  pg: "landing",
  role: "creator",
  tab: 0,
  step: 0,
  vs: 0,
  set: "Account",
  cat: "All",
  av: "🟢",
  sent: 0,
  saved: {},
};
try {
  document.documentElement.dataset.theme =
    localStorage.getItem("th") || "light";
} catch (e) {}
const av = (n, c, l) =>
  `<div class="av ${l ? "l" : ""}" style="background:${c || (P.find((p) => p[0] == n) || [0, "#FF6B00"])[1]}">${n
    .split(" ")
    .map((x) => x[0])
    .join("")}</div>`;
const vb = (v) =>
  v
    ? '<span class="ver" title="Profile & portfolio reviewed">✔ Verified</span>'
    : "";
const go = (p, x) => {
  S.pg = p;
  if (x) Object.assign(S, x);
  draw();
  scrollTo(0, 0);
};
const toast = (m) => {
  const t = document.createElement("div");
  t.className = "toast";
  t.textContent = m;
  document.body.append(t);
  setTimeout(() => t.remove(), 2200);
};
const theme = () => {
  const d = document.documentElement;
  d.dataset.theme = d.dataset.theme == "dark" ? "light" : "dark";
  try {
    localStorage.setItem("th", d.dataset.theme);
  } catch (e) {}
};
const modal = (h) => {
  const m = document.createElement("div");
  m.className = "mod";
  m.innerHTML = "<div>" + h + "</div>";
  m.onclick = (e) => {
    if (e.target == m) m.remove();
  };
  document.body.append(m);
};
const stat = (l, v, d) =>
  `<div class="card"><div class="mu">${l}</div><div style="font-size:26px;font-weight:700;margin:4px 0">${v}</div>${d ? `<span class="badge">${d}</span>` : ""}</div>`;
const edCard = (e) =>
  `<div class="card"><div class="th" style="background:${G[ED.indexOf(e)]}">▶</div><div class="row">${av(e.n)}<div><h3>${e.n} ${vb(e.v)}</h3><div class="mu">${e.s} · ${e.loc}</div></div></div><div class="row sp" style="margin:12px 0"><span>⭐ ${e.r} <span class="mu">(${e.p} projects)</span></span><b>from ${e.pr}</b></div><div class="row"><button class="btn g s" onclick="vp()">View Portfolio</button><button class="btn s" onclick="modal(hire('${e.n}'))">Hire</button></div></div>`;
const hire = (n) =>
  `<h2>Hire ${n}</h2><p class="mu">Send an invite with your project brief.</p><label>Select job</label><select><option>YouTube Series — 8 Episodes</option><option>Create new job…</option></select><label>Message</label><textarea rows=3>Hi! Loved your portfolio — would you be open to this project?</textarea><div class="row" style="margin-top:16px"><button class="btn" onclick="this.closest('.mod').remove();toast('Invite sent to ${n}')">Send Invite</button><button class="btn g" onclick="this.closest('.mod').remove();go('messages')">Message</button></div>`;
const jobCard = (j, i) =>
  `<div class="card"><div class="row sp"><span class="badge">${j.x}</span><span class="mu">${j.ago} ago</span></div><h3 style="margin:10px 0 4px">${j.t}</h3><div class="row mu">${av(j.c, 0)}${j.c}</div><p class="mu" style="margin:10px 0">Deliverables: 1080p/4K exports, 2 revisions · ${j.sw}</p><div class="row sp"><b>${j.b}</b><span class="mu">Due ${j.d} · ${j.a} applicants</span></div><div class="row" style="margin-top:12px">${S.role == "editor" ? `<button class="btn s" onclick="modal(apply(${i}))">Apply</button><button class="btn g s" onclick="S.saved[${i}]=1;toast('Job saved')">Save</button>` : `<button class="btn g s" onclick="toast('Showing ${j.a} applications')">Applications (${j.a})</button>`}<button class="btn g s" onclick="modal(jobd(${i}))">View Job</button></div></div>`;
const jobd = (i) => {
  const j = JOBS[i];
  return `<span class="badge">${j.x}</span><h2 style="margin-top:8px">${j.t}</h2><p class="mu">Posted by ${j.c} · ${j.ago} ago. We need a consistent editor with strong pacing, captions and clean sound. Brief and raw footage shared after hire.</p><div class="grid" style="margin:14px 0"><div><div class="mu">Budget</div><b>${j.b}</b></div><div><div class="mu">Deadline</div><b>${j.d}</b></div><div><div class="mu">Software</div><b>${j.sw}</b></div></div>${S.role == "editor" ? `<button class="btn" onclick="this.closest('.mod').remove();modal(apply(${i}))">Apply Now</button>` : ""}`;
};
const apply = (i) =>
  `<h2>Apply: ${JOBS[i].t}</h2><div class="card" style="padding:12px;background:var(--pl);border:0"><b>${JOBS[i].b}</b> · Due ${JOBS[i].d} · ${JOBS[i].c}</div><label>Cover message</label><textarea rows=3>Hi ${JOBS[i].c.split(" ")[0]}, I have edited 40+ videos for similar channels…</textarea><div class="row"><div><label>Proposed price</label><input value="${JOBS[i].b}"></div><div><label>Delivery time</label><input value="7 days"></div></div><label>Portfolio projects</label><select><option>Documentary Cut — Sofia</option><option>Reels Pack</option></select><div class="row" style="margin-top:16px"><button class="btn" onclick="this.closest('.mod').remove();toast('Application submitted ✔')">Submit Application</button></div>`;
const tabs = (a, k) =>
  `<div class="tabs">${a.map((t, i) => `<div class="tab ${S[k] == i ? "on" : ""}" onclick="S.${k}=${i};draw()">${t}</div>`).join("")}</div>`;

const NAV = () =>
  S.role == "creator"
    ? [
        ["dashboard", "🏠", "Dashboard"],
        ["community", "💬", "Community"],
        ["network", "👥", "Network"],
        ["explore", "🔍", "Explore"],
        ["jobs", "📋", "My Jobs"],
        ["postjob", "➕", "Post a Job"],
        ["messages", "✉️", "Messages"],
        ["profile", "👤", "Profile"],
        ["settings", "⚙️", "Settings"],
      ]
    : [
        ["dashboard", "🏠", "Dashboard"],
        ["community", "💬", "Community"],
        ["network", "👥", "Network"],
        ["jobs", "📋", "Find Jobs"],
        ["profile", "🎬", "Portfolio"],
        ["services", "🛍", "Services"],
        ["messages", "✉️", "Messages"],
        ["verify", "✔", "Verification"],
        ["settings", "⚙️", "Settings"],
      ];

const pages = {
  landing:
    () => `<div class="land"><div class="row sp"><div class="logo">Fra<b>me</b></div><div class="row"><button class="btn g s" onclick="theme();draw()">◐</button><button class="btn g s" onclick="go('auth',{mode:'login'})">Log in</button><button class="btn s" onclick="go('auth',{mode:'reg'})">Get Started</button></div></div>
<div class="hero"><div><span class="badge">For creators & creative talent</span><h1 style="margin:16px 0">Turn Your Ideas <span style="color:var(--pr)">Into Work.</span></h1><p class="mu" style="font-size:18px;margin-bottom:24px">Connect with creative talent, discover opportunities, and build your creative career.</p><div class="row wrap"><button class="btn" onclick="go('auth',{mode:'reg'})">Get Started</button><button class="btn g" onclick="go('explore',{role:'creator'})">Explore Creators</button></div></div><div class="blob">🎬<div class="card" style="position:absolute;left:-14px;bottom:24px;padding:12px;font-size:13px">✔ Verified editor · ⭐ 4.9</div></div></div>
<div class="grid stats">${[
      ["1,200+", "Creators"],
      ["800+", "Verified Editors"],
      ["3,500+", "Projects"],
      ["12,000+", "Connections"],
    ]
      .map((x) => stat(x[1], x[0]))
      .join("")}</div>
<h2 style="margin-top:50px">How it works</h2><div class="grid">${[
      ["1", "Share your project", "Post a brief or browse portfolios."],
      ["2", "Meet your talent", "Chat, compare work, invite editors."],
      ["3", "Create together", "Track projects, deliver, review."],
    ]
      .map(
        (x) =>
          `<div class="card"><span class="badge">${x[0]}</span><h3 style="margin:10px 0 4px">${x[1]}</h3><p class="mu">${x[2]}</p></div>`,
      )
      .join("")}</div>
<h2 style="margin-top:50px">Featured talent</h2><div class="grid">${ED.slice(0, 3).map(edCard).join("")}</div>
<div class="card" style="margin:50px 0;text-align:center;padding:44px;background:#111;color:#fff"><h2 style="font-size:30px">“Found my editor in one afternoon.”</h2><p style="opacity:.7;margin:8px 0 22px">— Maya Chen, 420K subscribers</p><button class="btn" onclick="go('auth',{mode:'reg'})">Join the community</button></div><p class="mu" style="text-align:center;padding-bottom:30px">© 2026 Frame · Prototype with placeholder data · <a onclick="go('adminlogin')">Admin access</a></p></div>`,
  auth: () =>
    `<div style="min-height:100vh;display:grid;place-items:center;padding:16px"><div class="card" style="max-width:520px;width:100%"><div class="logo">Fra<b>me</b></div>${
      S.mode == "login"
        ? `<h2>Welcome back</h2><label>Email</label><input value="abdullah@studio.com"><label>Password</label><input type=password value="password"><div class="row sp mu" style="margin:12px 0"><span><input type=checkbox style="width:auto"> Remember me</span><a>Forgot password?</a></div><button class="btn" style="width:100%" onclick="go('community')">Log in</button><button class="btn g" style="width:100%;margin-top:10px">Continue with Google</button><p class="mu" style="margin-top:14px">New here? <a onclick="S.mode='reg';draw()">Create account</a></p>`
        : `<h2>What brings you here?</h2><div class="grid" style="grid-template-columns:1fr 1fr">${[
            [
              "creator",
              "🎥",
              "I'm a Creator",
              "I want to build content and work with creative talent.",
            ],
            [
              "editor",
              "✂️",
              "I'm an Editor",
              "I want to showcase my work and find opportunities.",
            ],
          ]
            .map(
              (r) =>
                `<div class="card" style="cursor:pointer;padding:16px" onclick="S.role='${r[0]}';S.mode='login';toast('Signed up as ${r[0]}');go('community')"><div style="font-size:30px">${r[1]}</div><h3 style="margin:8px 0 4px">${r[2]}</h3><p class="mu">${r[3]}</p></div>`,
            )
            .join(
              "",
            )}</div><p class="mu" style="margin-top:14px">Have an account? <a onclick="S.mode='login';draw()">Log in</a></p>`
    }</div></div>`,
  community: () =>
    `<div class="cols"><aside class="card" style="height:fit-content"><h3 style="margin-bottom:8px">Categories</h3>${["All", "Video", "Design", "YouTube", "Social Media", "Freelancing", "Photography", "Motion", "Storytelling", "Career", "Learning"].map((c) => `<div class="ni ${S.cat == c ? "on" : ""}" onclick="S.cat='${c}';draw()">${c}</div>`).join("")}</aside><div><div class="card row" style="margin-bottom:16px">${av("Abdullah Khan", "#111")}<input placeholder="Share your latest project or ask the community…"><button class="btn s" onclick="toast('Posted!')">Post</button></div>${
      POSTS.filter(
        (p) => S.cat == "All" || p[2] == S.cat || (1 && S.cat == "All"),
      )
        .concat(
          S.cat == "All" ? [] : POSTS.filter((p) => p[2] != S.cat).slice(0, 0),
        )
        .map(
          (p, i) =>
            `<div class="card" style="margin-bottom:16px"><div class="row sp"><div class="row">${av(p[0])}<div><b>${p[0]}</b><div class="mu">${p[2]} · ${i + 1}h</div></div></div><button class="btn g s" onclick="toast('Following ${p[0]}')">Follow</button></div><p style="margin:12px 0">${p[1]}</p>${i % 2 ? `<div class="th" style="background:${G[i]}">▶</div>` : ""}<div class="row mu"><a onclick="this.textContent='♥ ${p[3] + 1}'">♡ ${p[3]}</a><span>💬 ${p[4]}</span><span>↗ Share</span><a onclick="toast('Saved')" style="margin-left:auto">Save</a></div></div>`,
        )
        .join("") || '<div class="card mu">No posts in this category yet.</div>'
    }</div><aside><div class="card"><h3>Trending</h3>${["#YouTubeWorkflow", "#ShortFormTips", "#EditorLife", "#SoundDesign"].map((t) => `<p style="margin-top:10px"><a>${t}</a><br><span class="mu">${Math.floor(Math.random() * 900 + 100)} posts</span></p>`).join("")}</div><div class="card" style="margin-top:16px"><h3>Suggested people</h3>${ED.slice(
      0,
      3,
    )
      .map(
        (e) =>
          `<div class="row" style="margin-top:12px">${av(e.n)}<div><b>${e.n.split(" ")[0]}</b><div class="mu">${e.s}</div></div></div>`,
      )
      .join("")}</div></aside></div>`,
  network: () =>
    `<h2>Your Network</h2>${tabs(["Suggested", "Following", "Followers", "Connections"], "tab")}<input placeholder="Search people, skills, roles…" style="margin-bottom:16px"><div class="grid">${P.map((p, i) => `<div class="card" style="text-align:center">${av(p[0]).replace('class="av"', 'class="av" style="margin:auto;width:64px;height:64px;background:' + p[1] + '"')}<h3 style="margin-top:10px">${p[0]} ${i < 4 ? '<span class="ver">✔</span>' : ""}</h3><div class="mu">@${p[0].split(" ")[0].toLowerCase()} · ${["Content Creator", "Video Editor", "Motion Designer", "YouTube Creator", "Social Media Manager", "Podcast Editor"][i]}</div><div style="margin:10px 0"><span class="badge">Premiere</span> <span class="badge">Storytelling</span></div><div class="row" style="justify-content:center"><button class="btn s" onclick="this.textContent='Requested ✓'">Connect</button><button class="btn g s" onclick="vp()">Profile</button></div></div>`).join("")}</div>`,
  explore: () =>
    `<h2>Explore</h2>${tabs(["Editors", "Projects", "Services", "Creators"], "tab")}<div class="row wrap" style="margin-bottom:16px">${["Specialization", "Software", "Rating 4.5+", "Availability", "Price range"].map((f) => `<span class="tab">${f} ▾</span>`).join("")}</div><div class="grid">${(S.tab == 0 ? ED : ED).map(edCard).join("")}</div>`,
  dashboard: () => {
    const c = S.role == "creator";
    return `<h2 style="font-size:28px">Good morning, Abdullah 👋</h2><p class="mu" style="margin-bottom:18px">Here's what's happening with your creative work.</p><div class="grid stats">${(c
      ? [
          ["Videos", 48, "+6"],
          ["Active Projects", 5],
          ["Completed", 32],
          ["Hired Editors", 7],
          ["Total Spent", "$12.4k"],
        ]
      : [
          ["Earnings", "$8,240", "+12%"],
          ["Active Projects", 4],
          ["Completed", 67],
          ["Videos Delivered", 214],
          ["Clients", 23],
          ["Profile Views", "1.9k", "+18%"],
        ]
    )
      .map((s) => stat(...s))
      .join(
        "",
      )}</div><div class="grid" style="margin-top:16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">${
      c
        ? `<div class="card"><h3>Active projects</h3>${[
            ["YouTube Series — Ep 04", 70],
            ["Podcast Clips", 40],
            ["Brand Reel", 90],
          ]
            .map(
              (p) =>
                `<p style="margin-top:12px">${p[0]} <span class="mu">${p[1]}%</span></p><div class="prog"><i style="width:${p[1]}%"></i></div>`,
            )
            .join("")}</div><div class="card"><h3>Growth</h3>${[
            ["Content consistency", 82],
            ["Projects completed", 64],
            ["Network growth", 48],
          ]
            .map(
              (p) =>
                `<p style="margin-top:12px">${p[0]}</p><div class="prog"><i style="width:${p[1]}%"></i></div>`,
            )
            .join("")}</div>`
        : `<div class="card"><h3>Monthly earnings</h3><div class="bar" style="margin-top:14px">${[40, 55, 48, 70, 62, 90].map((h) => `<i style="height:${h}%"></i>`).join("")}</div><div class="row sp mu"><span>May</span><span>Oct</span></div></div><div class="card"><h3>Recommended jobs</h3>${JOBS.slice(
            0,
            3,
          )
            .map(
              (j, i) =>
                `<p style="margin-top:12px"><a onclick="modal(jobd(${i}))">${j.t}</a><br><span class="mu">${j.b} · ${j.c}</span></p>`,
            )
            .join("")}</div>`
    }</div>${c ? `<h2 style="margin:24px 0 12px">Recommended editors</h2><div class="grid">${ED.slice(0, 3).map(edCard).join("")}</div>` : ""}`;
  },
  jobs: () =>
    `<div class="row sp wrap"><h2>${S.role == "editor" ? "Find Jobs" : "My Jobs"}</h2>${S.role == "creator" ? `<button class="btn" onclick="go('postjob',{step:0})">+ Post a Job</button>` : ""}</div>${tabs(S.role == "editor" ? ["Recommended", "Saved", "Applied"] : ["Active", "Drafts", "Closed"], "tab")}<div class="row wrap" style="margin-bottom:16px"><input placeholder="Search jobs…" style="max-width:280px">${["Category", "Budget", "Software", "Deadline"].map((f) => `<span class="tab">${f} ▾</span>`).join("")}</div><div class="grid">${JOBS.map(jobCard).join("")}</div>`,
  postjob: () => {
    const st = [
      "Basics",
      "Requirements",
      "Budget",
      "Timeline",
      "Deliverables",
      "Review",
    ];
    const f = [
      `<label>Job title</label><input value="YouTube Series — Episode Editing"><label>Content type</label><select><option>Long-form YouTube</option><option>Short-form</option></select><label>Description</label><textarea rows=4>8 episodes of a tech-explainer series…</textarea>`,
      `<label>Video quantity</label><input value="8"><label>Length</label><input value="10–14 min"><label>Software</label><input value="Premiere Pro"><label>Skills</label><input value="Pacing, sound design, captions">`,
      `<label>Type</label><select><option>Fixed price</option><option>Range</option></select><label>Budget</label><input value="$1,800">`,
      `<label>Start</label><input type=date><label>Deadline</label><input type=date>`,
      `<label>Format</label><input value="MP4 H.264"><label>Resolution</label><select><option>4K</option><option>1080p</option></select><label>Revisions</label><input value="2">`,
      `<div class="card" style="background:var(--pl);border:0"><span class="badge">Preview</span><h3 style="margin:8px 0">YouTube Series — Episode Editing</h3><p class="mu">8 videos · Premiere Pro · $1,800 · 4K · 2 revisions</p></div>`,
    ];
    return `<div style="max-width:640px;margin:auto"><h2>Post a Job</h2><div class="row" style="margin-bottom:8px"><span class="mu">Step ${S.step + 1}/6 — ${st[S.step]}</span></div><div class="prog"><i style="width:${((S.step + 1) * 100) / 6}%"></i></div><div class="card" style="margin-top:16px">${f[S.step]}<div class="row sp" style="margin-top:20px"><button class="btn g" ${S.step ? "" : "disabled"} onclick="S.step--;draw()">Back</button>${S.step < 5 ? `<button class="btn" onclick="S.step++;draw()">Next</button>` : `<button class="btn" onclick="toast('Job published 🎉');go('jobs')">Publish Job</button>`}</div></div></div>`;
  },
  profile: () =>
    `<div class="card"><div class="row wrap">${av("Sofia Reyes", 0, 1)}<div style="flex:1"><h2 style="margin:0">Sofia Reyes ${vb(1)}</h2><p class="mu">YouTube & Documentary Editor · Madrid</p><p class="mu">⭐ 4.9 · 86 projects · 41 clients · <span>🟢 Available</span></p></div><div class="row wrap"><button class="btn" onclick="go('messages')">Contact</button><button class="btn g" onclick="modal(hire('Sofia Reyes'))">Invite to Project</button><button class="btn g" onclick="go('services')">View Services</button></div></div></div><h2 style="margin:22px 0 12px">Portfolio</h2><div class="grid">${["Documentary: The Last Fishers", "Tech Review Series", "Podcast Highlights", "Travel Vlog Ep. 12", "Brand Story Reel", "Gaming Montage"].map((t, i) => `<div class="card" style="cursor:pointer;padding:12px" onclick="modal(proj(${i}))"><div class="th" style="background:${G[i]};height:170px">▶</div><b>${t}</b><div class="mu">${["Documentary", "YouTube", "Podcast", "Vlog", "Brand", "Gaming"][i]} · Premiere, DaVinci</div></div>`).join("")}</div><h2 style="margin:22px 0 12px">Reviews</h2><div class="card">“Fast, thoughtful, and the pacing was perfect.” <p class="mu">— Jordan Ellis ⭐⭐⭐⭐⭐</p></div>`,
  services: () =>
    `<h2>Services</h2><div class="card" style="margin-bottom:16px"><b>Open to work</b><div class="row wrap" style="margin-top:10px">${["🟢 Available", "🟡 Limited", "🔴 Not available"].map((a) => `<span class="tab ${S.av == a[0] ? "on" : ""}" onclick="S.av='${a[0]}';draw()">${a}</span>`).join("")}</div></div><div class="grid">${[
      ["YouTube Video Editing", "$120", "3–5 days"],
      ["Short-form Content", "$60", "48 hours"],
      ["Podcast Editing", "$80", "3 days"],
      ["Motion Graphics", "$200", "7 days"],
    ]
      .map(
        (s) =>
          `<div class="card"><h3>${s[0]}</h3><p class="mu">2 revisions · Premiere, After Effects</p><div class="row sp" style="margin-top:10px"><b>from ${s[1]}</b><span class="mu">${s[2]}</span></div></div>`,
      )
      .join(
        "",
      )}<div class="card" style="display:grid;place-items:center"><button class="btn g" onclick="toast('Service editor opens here')">+ New service</button></div></div>`,
  messages: () =>
    `<div class="card chat"><div><input placeholder="Search…" style="margin-bottom:10px">${ED.slice(
      0,
      3,
    )
      .map(
        (e, i) =>
          `<div class="row" style="padding:10px 0">${av(e.n)}<div><b>${e.n}</b> ${i < 2 ? "🟢" : ""}<div class="mu">Sounds good, sending v2…</div></div></div>`,
      )
      .join(
        "",
      )}</div><div style="display:flex;flex-direction:column"><b>Sofia Reyes ${vb(1)}</b><div style="flex:1"><div class="bub">Hi! Rough cut of Episode 04 is ready.</div><div class="bub me">Amazing — can we tighten the intro?</div><div class="bub">Sure. Sending v2 tomorrow 📎 ep04_v2.mp4</div>${S.sent ? '<div class="bub me">Thanks!</div>' : ""}</div><div class="row"><span>📎</span><span>😊</span><input placeholder="Write a message…"><button class="btn s" onclick="S.sent=1;draw()">Send</button></div></div><div style="border:0"><span class="badge">Project</span><h3 style="margin:8px 0">YouTube Series — Episode 04</h3><p class="mu" style="margin:6px 0">Budget: $1,800<br>Deadline: Oct 30<br>Deliverables: 4K MP4, thumbnail cut<br>Status: In progress</p><button class="btn s" onclick="go('jobs')">View Project</button></div></div>`,
  settings: () =>
    `<h2>Settings</h2>${tabs(["Account", "Appearance", "Notifications", "Privacy", "Security"], "vs")}<div class="card" style="max-width:600px">${[`<div class="row">${av("Abdullah Khan", "#111")}<button class="btn g s">Change photo</button></div><label>Name</label><input value="Abdullah Khan"><label>Email</label><input value="abdullah@studio.com"><label>Username</label><input value="@abdullah">`, `<div class="row sp"><span>Dark mode</span><button class="btn g s" onclick="theme();draw()">Toggle</button></div>`, ["Messages", "Job applications", "Community", "Projects"].map((x) => `<div class="row sp" style="padding:8px 0"><span>${x}</span><input type=checkbox checked style="width:auto"></div>`).join(""), `<label>Profile visibility</label><select><option>Public</option><option>Connections only</option></select><label>Who can contact me</label><select><option>Everyone</option><option>Verified only</option></select>`, `<label>Current password</label><input type=password><label>New password</label><input type=password><p class="mu" style="margin-top:12px">2FA: coming soon · 2 active sessions</p>`][S.vs]}<button class="btn" style="margin-top:16px" onclick="toast('Saved')">Save</button></div>`,
  verify: () => {
    const st = [
      "Personal",
      "Professional",
      "Skills",
      "Software",
      "Experience",
      "Portfolio",
      "Samples",
      "Availability",
      "Review",
    ];
    if (S.step > 8)
      return `<div class="card" style="max-width:520px;margin:auto;text-align:center;padding:40px"><div style="font-size:44px">📨</div><h2>Application submitted</h2><span class="badge">Under Review</span><p class="mu" style="margin-top:12px">We'll email you within 5 business days. Verification reflects a profile & portfolio review, not a guarantee of project outcomes.</p></div>`;
    const n = Math.min(S.step, 8);
    return `<div style="max-width:620px;margin:auto"><h2>Become a Verified Editor</h2><p class="mu">Verified editors have completed our profile and portfolio review process.</p><p class="mu" style="margin-top:12px">Step ${n + 1}/9 — ${st[n]}</p><div class="prog"><i style="width:${((n + 1) * 100) / 9}%"></i></div><div class="card" style="margin-top:16px"><label>${st[n]} details</label><input placeholder="Full name / professional name / URL…"><label>Notes</label><textarea rows=3 placeholder="Tell us more"></textarea>${n == 5 || n == 6 ? '<div class="card" style="margin-top:12px;text-align:center;border-style:dashed">Upload video, image or link</div>' : ""}<div class="row sp" style="margin-top:20px"><button class="btn g" ${n ? "" : "disabled"} onclick="S.step=${n}-1;draw()">Back</button><button class="btn" onclick="S.step=${n}+1;draw()">${n < 8 ? "Next" : "Submit"}</button></div></div></div>`;
  },
};
const proj = (i) =>
  `<div class="th" style="background:${G[i]};height:200px">▶</div><h2>Project details</h2><p class="mu">Client brief: 12-min narrative documentary for a 300K channel. Role: lead editor & sound designer. Tools: Premiere Pro, DaVinci. Timeline: 3 weeks.</p><p style="margin:10px 0"><span class="badge">+42% avg. watch time</span> <span class="badge">1.2M views</span></p><p class="mu">“Exceeded expectations.” — Client review ⭐⭐⭐⭐⭐</p>`;
function draw() {
  const d = document.documentElement;
  if (S.pg == "landing" || S.pg == "auth" || S.pg == "adminlogin") {
    $("#r").innerHTML = pages[S.pg]();
    return;
  }
  if (S.area == "admin") {
    $("#r").innerHTML = renderAdmin();
    return;
  }
  const n = NAV();
  $("#r").innerHTML =
    `<div class="app"><nav class="side"><div class="logo">Fra<b>me</b></div>${n.map((x) => `<div class="ni ${S.pg == x[0] ? "on" : ""}" onclick="go('${x[0]}',{tab:0,step:0,viewEd:0,pt:0})">${x[1]} ${x[2]}</div>`).join("")}</nav><div><div class="top"><input placeholder="Search creators, editors, jobs…"><span class="tab" onclick="S.role=S.role=='creator'?'editor':'creator';go('dashboard')" title="Switch role (demo)">Role: ${S.role}</span><span style="cursor:pointer" onclick="toast('3 new notifications')">🔔</span><span style="cursor:pointer" onclick="go('messages')">✉️</span><span style="cursor:pointer" onclick="theme();draw()">◐</span><span onclick="go('landing')" style="cursor:pointer">${av("Abdullah Khan", "#111")}</span></div><div class="main">${pages[S.pg]()}</div></div></div><div class="mob">${n
      .slice(0, 5)
      .map(
        (x) =>
          `<span class="${S.pg == x[0] ? "on" : ""}" onclick="go('${x[0]}',{tab:0,step:0,viewEd:0,pt:0})"><b>${x[1]}</b>${x[2].split(" ")[0]}</span>`,
      )
      .join("")}</div>`;
}
/* ===== v2 upgrades ===== */
const star = (r) => "★".repeat(Math.round(r)) + "☆".repeat(5 - Math.round(r));
const vp = () => go("profile", { viewEd: 1 });
const gv = (i) => `background:${G[i % 6]}`;
const th = (i, h, t) =>
  `<div class="th" style="${gv(i)};height:${h || 130}px">${t || "▶"}</div>`;
const bars = (a, l) =>
  `<div class="bar">${a.map((h) => `<i class="gr" style="height:${h}%"></i>`).join("")}</div><div class="row sp mu"><span>${l[0]}</span><span>${l[l.length - 1]}</span></div>`;
const st2 = (ic, l, v, d) =>
  `<div class="card hv"><div class="row"><span class="badge" style="font-size:15px">${ic}</span><span class="mu">${l}</span></div><div style="font-size:28px;font-weight:700;margin:8px 0 2px">${v}</div>${d ? `<span style="color:#10B981;font-size:13px">▲ ${d}</span>` : '<span class="mu">&nbsp;</span>'}</div>`;
const MJ = [
  {
    t: "YouTube Episode #12",
    e: "Sarah Ahmed",
    sp: "Video Editor",
    s: "In Progress",
    p: 72,
    d: "Oct 04",
    la: "2h ago",
    k: "a",
    ty: "YouTube",
    gi: 0,
  },
  {
    t: "Podcast Episode 09 Clips",
    e: "Tariq Aziz",
    sp: "Podcast Editor",
    s: "In Review",
    p: 90,
    d: "Oct 01",
    la: "Yesterday",
    k: "a",
    ty: "Podcast",
    gi: 2,
  },
  {
    t: "Brand Reel Q4",
    e: "Omar Haddad",
    sp: "Motion Designer",
    s: "In Progress",
    p: 40,
    d: "Oct 12",
    la: "5h ago",
    k: "a",
    ty: "Motion",
    gi: 1,
  },
  {
    t: "YouTube Video Editing",
    e: "Sarah Ahmed",
    sp: "Video Editor",
    s: "Completed",
    p: 100,
    d: "Sep 20",
    k: "c",
    r: 5,
    ty: "YouTube",
    gi: 3,
  },
  {
    t: "Podcast Episode 08",
    e: "Sarah Ahmed",
    sp: "Video Editor",
    s: "Completed",
    p: 100,
    d: "Sep 12",
    k: "c",
    r: 5,
    ty: "Podcast",
    gi: 4,
  },
  {
    t: "Shorts Pack Vol. 2",
    e: "Lena Novak",
    sp: "Short-form Editor",
    s: "Completed",
    p: 100,
    d: "Aug 30",
    k: "c",
    r: 4,
    ty: "Short-form",
    gi: 5,
  },
  {
    t: "Gaming Intro Pack",
    e: "Alex Morgan",
    sp: "Motion Designer",
    s: "Editor unavailable",
    p: 35,
    d: "—",
    k: "h",
    why: "Editor became unavailable",
    ty: "Motion",
    gi: 1,
  },
  {
    t: "Course Promo Cut",
    e: "Nina Park",
    sp: "Video Editor",
    s: "Paused",
    p: 20,
    d: "—",
    k: "h",
    why: "Paused by you",
    ty: "Education",
    gi: 2,
  },
  {
    t: "YouTube Shorts Editor",
    k: "p",
    b: "$150–$250",
    apps: 12,
    posted: "2 days ago",
    d: "Oct 20",
    gi: 2,
  },
  {
    t: "Instagram Reels Monthly",
    k: "p",
    b: "$400/mo",
    apps: 8,
    posted: "5 days ago",
    d: "Ongoing",
    gi: 4,
  },
];
const AP = [
  {
    bio: "Documentary & YouTube storyteller, 6 yrs.",
    pr: "$220",
    dl: "5 days",
    sk: ["Pacing", "Sound design", "Captions"],
  },
  {
    bio: "Motion-first editor for punchy shorts.",
    pr: "$180",
    dl: "4 days",
    sk: ["After Effects", "Titles", "Reels"],
  },
  {
    bio: "Fast-turnaround short-form specialist.",
    pr: "$150",
    dl: "3 days",
    sk: ["CapCut", "Hooks", "Subtitles"],
  },
  {
    bio: "Audio-focused editor, podcast clips.",
    pr: "$170",
    dl: "4 days",
    sk: ["Audio", "Clips", "Resolve"],
  },
];
const jm = (i) => MJ[i];
const eRow = (j) =>
  `<div class="row">${av(j.e)}<div><b>${j.e}</b><div class="mu">${j.sp}</div></div></div>`;
const jBtn = (i, m) =>
  `<button class="btn s" onclick="go('jobdetail',{job:${i}})">View Job</button>${m ? `<button class="btn g s" onclick="go('messages')">Message Editor</button>` : ""}`;
const actCard = (j, i) =>
  `<div class="card hv"><div class="row sp"><h3>${j.t}</h3><span class="badge">${j.s}</span></div><p class="mu" style="margin:10px 0 4px">Working with</p>${eRow(j)}<div class="row sp" style="margin:12px 0 6px"><span class="mu">Progress</span><b>${j.p}%</b></div><div class="prog"><i style="width:${j.p}%"></i></div><div class="row sp mu" style="margin:8px 0 12px"><span>Deadline ${j.d}</span><span>Active ${j.la}</span></div><div class="row wrap">${jBtn(i, 1)}</div></div>`;
const reopen = (i) => {
  const j = MJ[i];
  j.k = "p";
  j.again = 1;
  j.b = "$300";
  j.apps = 0;
  j.posted = "just now";
  JOBS.unshift({
    t: j.t,
    c: "Abdullah Khan",
    b: "$300",
    d: "Nov 1",
    sw: "Premiere Pro",
    a: 0,
    ago: "now",
    x: j.ty,
  });
  toast("Job reopened — now visible to editors");
  draw();
};
const chartTabs = ["Videos", "Projects", "Collaborations", "Connections"],
  CD = [
    [30, 42, 38, 55, 60, 72],
    [20, 28, 35, 30, 48, 55],
    [10, 18, 22, 30, 34, 45],
    [25, 30, 45, 50, 66, 80],
  ];
const donut = (a, c) => {
  let s = 0;
  const g = a
    .map((v, i) => {
      const x = `${c[i]} ${s}% ${s + v}%`;
      s += v;
      return x;
    })
    .join(",");
  return `<div class="donut" style="background:conic-gradient(${g})"></div>`;
};
const leg = (n, a, c) =>
  n
    .map(
      (x, i) =>
        `<div class="row" style="gap:8px;font-size:13px"><i style="width:10px;height:10px;border-radius:3px;background:${c[i]}"></i>${x} <span class="mu">${a[i]}%</span></div>`,
    )
    .join("");
const OR = ["#FF6B00", "#FF9A4D", "#FFC599", "#9CA3AF", "#4B5563"];

const oldJobs = pages.jobs;
pages.dashboard = () => {
  if (S.role == "editor")
    return `<h2 style="font-size:28px">Good morning, Abdullah 👋</h2><p class="mu" style="margin-bottom:18px">Your work, reputation and growth at a glance.</p>
<div class="grid stats">${[
      ["💰", "Total Earnings", "$8,240", "12%"],
      ["🎬", "Active Projects", 4],
      ["✅", "Completed", 67],
      ["📦", "Videos Delivered", 214, "18"],
      ["🤝", "Clients", 23],
      ["👁", "Profile Views", "1.9k", "18%"],
    ]
      .map((s) => st2(...s))
      .join("")}</div>
<div class="grid stats" style="margin-top:12px">${[
      ["Repeat Clients", "61%"],
      ["Avg. Rating", "4.9 ★"],
      ["Applications Sent", 38],
      ["Success Rate", "34%"],
    ]
      .map(
        (s) =>
          `<div class="card" style="padding:14px"><div class="mu">${s[0]}</div><b style="font-size:20px">${s[1]}</b></div>`,
      )
      .join("")}</div>
<div class="grid" style="margin-top:16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))"><div class="card"><h3>Monthly Earnings</h3><p class="mu">Up 12% vs last quarter</p>${bars([40, 55, 48, 70, 62, 90], ["May", "Jun", "Jul", "Aug", "Sep", "Oct"])}</div><div class="card"><h3>Projects Completed</h3>${bars([30, 45, 40, 60, 75, 68], ["May", "Oct"])}</div>
<div class="card"><h3>Client Relationships</h3><p class="mu" style="margin-bottom:10px">Total 23 clients</p>${[
      ["New", 9, 40],
      ["Returning", 14, 61],
    ]
      .map(
        (x) =>
          `<div class="row sp"><span>${x[0]}</span><b>${x[1]}</b></div><div class="prog" style="margin-bottom:10px"><i style="width:${x[2]}%"></i></div>`,
      )
      .join("")}</div>
<div class="card"><h3>Work Type</h3><div class="row wrap" style="margin-top:10px">${donut([38, 24, 16, 12, 10], OR)}<div>${leg(["YouTube", "Short-form", "Podcast", "Motion", "Social"], [38, 24, 16, 12, 10], OR)}</div></div></div>
<div class="card"><h3>Profile Performance</h3>${[
      ["Profile views", "1.9k", 80],
      ["Portfolio views", "3.4k", 90],
      ["Applications", "38", 50],
      ["Application success", "34%", 34],
      ["Connections", "286", 60],
    ]
      .map(
        (x) =>
          `<div class="row sp" style="margin-top:10px"><span class="mu">${x[0]}</span><b>${x[1]}</b></div><div class="prog"><i style="width:${x[2]}%"></i></div>`,
      )
      .join("")}</div>
<div class="card"><h3>✨ Your Professional Highlights</h3>${[
      ["Top skill", "Storytelling & pacing"],
      ["Most completed", "YouTube long-form"],
      ["Frequent client", "Solo YouTube creators"],
      ["Repeat clients", "61%"],
      ["Avg. rating", "4.9 / 5"],
      ["Most viewed project", "The Last Fishers"],
    ]
      .map(
        (x) =>
          `<div class="row sp" style="padding:6px 0"><span class="mu">${x[0]}</span><b>${x[1]}</b></div>`,
      )
      .join("")}</div></div>
<h2 style="margin:24px 0 12px">Your Work</h2><div class="grid">${[
      [
        "Tech Review Ep. 12",
        "Jordan Ellis",
        "YouTube",
        "In Progress",
        70,
        "Oct 04",
      ],
      ["Weekly Shorts", "Maya Chen", "Short-form", "In Review", 90, "Oct 02"],
      ["Podcast Clips", "Studio Loop", "Podcast", "Completed", 100, "Sep 28"],
      [
        "Intro Motion Pack",
        "Nova Plays",
        "Motion",
        "Pending application",
        0,
        "Nov 05",
      ],
    ]
      .map(
        (p, i) =>
          `<div class="card hv" style="padding:12px">${th(i, 110)}<b>${p[0]}</b><div class="mu">${p[1]} · ${p[2]} · Due ${p[5]}</div><div class="row sp" style="margin:8px 0"><span class="badge">${p[3]}</span><span class="mu">${p[4]}%</span></div><div class="prog"><i style="width:${p[4]}%"></i></div><button class="btn g s" style="margin-top:10px" onclick="toast('Opening project')">View Project</button></div>`,
      )
      .join("")}</div>
<h2 style="margin:24px 0 12px">Recommended jobs</h2><div class="grid">${JOBS.slice(0, 2).map(jobCard).join("")}</div>`;
  const act = MJ.map((j, i) => [j, i]).filter((x) => x[0].k == "a");
  return `<h2 style="font-size:28px">Good morning, Abdullah 👋</h2><p class="mu" style="margin-bottom:14px">Here's what's happening with your creative work.</p>
<div class="qa row" style="overflow:auto;margin-bottom:16px">${[
    ["➕", "Post a Job", "go('postjob',{step:0})"],
    ["🔍", "Find an Editor", "go('explore')"],
    ["🎬", "Explore Work", "go('explore')"],
    ["📋", "View My Jobs", "go('jobs')"],
    ["✉️", "Open Messages", "go('messages')"],
    ["👥", "View Network", "go('network')"],
    ["✍️", "Create a Post", "go('community')"],
  ]
    .map((a) => `<span class="tab" onclick="${a[2]}">${a[0]} ${a[1]}</span>`)
    .join("")}</div>
<div class="grid stats">${[
    ["👥", "Followers", "12.8K", "8.4% this month"],
    ["🎥", "Videos", 128, "12 this month"],
    ["⚡", "Active Projects", 4],
    ["✅", "Completed Projects", 37],
    ["✂️", "Hired Editors", 14],
    ["🔗", "Connections", 286, "9 this month"],
  ]
    .map((s) => st2(...s))
    .join("")}</div>
<div class="card" style="margin-top:16px"><div class="row sp wrap"><div><h3>Content & Project Activity</h3><p class="mu">Your creative activity is growing.</p></div></div>${tabs(chartTabs, "ct").replace(/^/, "")}${bars(CD[S.ct || 0], ["Apr", "May", "Jun", "Jul", "Aug", "Sep"])}</div>
<div class="row sp" style="margin:24px 0 12px"><h2 style="margin:0">Active Jobs</h2><a onclick="go('jobs',{jt:1})">See all</a></div><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">${act.map((x) => actCard(...x)).join("")}</div>
<div class="row sp" style="margin:24px 0 12px"><h2 style="margin:0">People You've Worked With</h2><a onclick="go('network')">View Network</a></div><div class="grid">${[
    ["Sarah Ahmed", "Video Editor", 8, 5, "YouTube Series"],
    ["Alex Morgan", "Motion Designer", 5, 5, "Brand Reel"],
    ["Lena Novak", "Short-form Editor", 3, 4, "Shorts Pack Vol. 2"],
  ]
    .map(
      (p) =>
        `<div class="card hv"><div class="row">${av(p[0])}<div><b>${p[0]}</b><div class="mu">${p[1]}</div></div></div><p style="margin:10px 0 2px">${p[2]} projects together <span style="color:var(--pr)">${star(p[3])}</span></p><p class="mu">Last project: ${p[4]}</p><button class="btn g s" style="margin-top:10px" onclick="vp()">View Profile</button></div>`,
    )
    .join("")}</div>
<h2 style="margin:24px 0 12px">Recent Work</h2><div class="grid">${MJ.filter(
    (j) => j.k == "c" || j.k == "a",
  )
    .slice(0, 4)
    .map(
      (j, i) =>
        `<div class="card hv" style="padding:12px">${th(j.gi, 120)}<b>${j.t}</b><div class="mu">Edited by ${j.e} · ${j.ty} · ${j.d}</div><div class="row sp" style="margin-top:10px"><span class="badge">${j.s}</span><a onclick="go('jobdetail',{job:${MJ.indexOf(j)}})">View Project</a></div></div>`,
    )
    .join("")}</div>
<h2 style="margin:24px 0 12px">Upcoming</h2><div class="card">${[
    ["⏰", "Oct 01", "Podcast Episode 09 — review needed"],
    ["⏰", "Oct 04", "YouTube Episode #12 — deadline"],
    ["📩", "Now", "12 applications for YouTube Shorts Editor"],
    ["🕒", "Pending", "Reels Monthly waiting for selection"],
  ]
    .map(
      (u) =>
        `<div class="row" style="padding:10px 0;border-bottom:1px solid var(--bd)"><span class="badge">${u[0]}</span><b style="min-width:70px">${u[1]}</b><span class="mu">${u[2]}</span></div>`,
    )
    .join("")}</div>`;
};

pages.jobs = () => {
  if (S.role == "editor") return oldJobs();
  const T = ["c", "a", "h", "p"],
    k = T[S.jt || 0];
  const list = MJ.map((j, i) => [j, i]).filter((x) => x[0].k == k);
  const body =
    list
      .map(([j, i]) =>
        k == "a"
          ? actCard(j, i)
          : k == "c"
            ? `<div class="card hv"><div class="row sp"><h3>${j.t}</h3><span class="badge">Completed</span></div><div style="margin:10px 0">${eRow(j)}</div><p class="mu">${j.ty} · Completed ${j.d}</p><p style="color:var(--pr);margin:6px 0 12px">${star(j.r)}</p><div class="row wrap">${jBtn(i)}<button class="btn g s" onclick="vp()">View Editor</button></div></div>`
            : k == "h"
              ? `<div class="card hv"><div class="row sp"><h3>${j.t}</h3><span class="badge">${j.s}</span></div><div style="margin:10px 0">${eRow(j)}</div><p class="mu">${j.why} · ${j.p}% done</p><div class="row wrap" style="margin-top:12px"><button class="btn s" onclick="reopen(${i})">Open for Applications</button><button class="btn g s" onclick="go('explore')">Find a New Editor</button></div></div>`
              : `<div class="card hv"><div class="row sp"><h3>${j.t}</h3>${j.again ? '<span class="badge">Open Again</span>' : '<span class="badge">Posted</span>'}</div><p class="mu" style="margin:10px 0">Posted ${j.posted} · Deadline ${j.d}</p><div class="row sp"><b>${j.b}</b><span class="mu">${j.apps} applications</span></div><button class="btn s" style="margin-top:12px;width:100%" onclick="go('applications',{job:${i}})">View Applications</button></div>`,
      )
      .join("") || '<div class="card mu">Nothing here yet.</div>';
  return `<div class="row sp wrap"><h2>My Jobs</h2><button class="btn" onclick="go('postjob',{step:0})">+ Post a Job</button></div>${tabs(
    ["Completed", "Active", "Not Completed / On Hold", "Posted"].map(
      (t, i) => t + " (" + MJ.filter((j) => j.k == T[i]).length + ")",
    ),
    "jt",
  )}<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">${body}</div>`;
};

pages.jobdetail = () => {
  const j = MJ[S.job || 0];
  return `<a onclick="go('jobs')">← My Jobs</a><div class="card" style="margin-top:12px"><span class="badge">${j.s || "Posted"}</span><h2 style="margin:10px 0">${j.t}</h2>${j.e ? `<div class="row sp wrap">${eRow(j)}<div class="row"><button class="btn g s" onclick="go('messages')">Message Editor</button><button class="btn g s" onclick="vp()">View Editor</button></div></div><div class="prog" style="margin:16px 0 6px"><i style="width:${j.p}%"></i></div><p class="mu">${j.p}% · Deadline ${j.d}</p>` : `<button class="btn s" onclick="go('applications',{job:${S.job}})">View Applications</button>`}</div><div class="grid" style="margin-top:16px">${th(j.gi, 180)}<div class="card"><h3>Brief</h3><p class="mu">Deliverables: 4K MP4, thumbnail cut, 2 revisions. Timeline: 3 weeks.</p></div></div>`;
};

pages.applications = () => {
  const j = MJ[S.job] || MJ[8];
  return `<a onclick="go('jobs',{jt:3})">← My Jobs</a><div class="card" style="margin:12px 0"><h2>${j.t}</h2><p class="mu">${j.b} · Deadline ${j.d} · ${AP.length} shown of ${j.apps} applications</p></div><div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">${ED.map((e, i) => `<div class="card hv"><div class="row">${av(e.n)}<div><b>${e.n}</b> ${vb(e.v)}<div class="mu">${e.s} · ⭐ ${e.r} · ${e.p} projects</div></div></div><p class="mu" style="margin:10px 0">${AP[i].bio}</p><div>${AP[i].sk.map((s) => `<span class="badge">${s}</span> `).join("")}</div><div class="row" style="margin:12px 0">${th(i, 70).replace('class="th"', 'class="th" style="flex:1;margin:0;')}${th(i + 2, 70).replace('class="th"', 'class="th" style="flex:1;margin:0;')}</div><div class="row sp"><b>${AP[i].pr}</b><span class="mu">Delivery ${AP[i].dl}</span></div><div class="row wrap" style="margin-top:12px"><button class="btn s" onclick="go('appdetail',{app:${i}})">View Application</button><button class="btn g s" onclick="vp()">Portfolio</button><button class="btn g s" onclick="go('messages')">Message</button><button class="btn g s" onclick="toast('${e.n} selected')">Select</button></div></div>`).join("")}</div>`;
};

pages.appdetail = () => {
  const e = ED[S.app || 0],
    a = AP[S.app || 0];
  return `<a onclick="go('applications')">← Applications</a><div class="card" style="margin:12px 0"><div class="row wrap">${av(e.n, 0, 1)}<div style="flex:1"><h2 style="margin:0">${e.n} ${vb(e.v)}</h2><p class="mu">${e.s} · ${e.loc} · ⭐ ${e.r} · 🟢 Available</p></div><button class="btn" onclick="toast('${e.n} selected as editor')">Select Editor</button><button class="btn g" onclick="go('messages')">Message</button></div></div><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))"><div class="card"><h3>Cover message</h3><p class="mu" style="margin-top:8px">Hi! I've edited 40+ videos for channels like yours. I'd love to help you tighten pacing and keep viewers to the end.</p><h3 style="margin-top:14px">Relevant experience</h3><p class="mu">${a.bio} ${e.p} projects delivered.</p></div><div class="card"><h3>Offer</h3><p style="margin-top:8px"><b>${a.pr}</b> · ${a.dl} delivery</p><h3 style="margin-top:14px">Skills & software</h3>${a.sk.map((s) => `<span class="badge">${s}</span> `).join("")}<p class="mu" style="margin-top:8px">Premiere Pro, After Effects, DaVinci</p></div></div><h2 style="margin:20px 0 12px">Relevant projects</h2><div class="grid">${[0, 1, 2].map((i) => `<div class="card hv" style="padding:12px;cursor:pointer" onclick="modal(proj(${i}))">${th(e.n.length + i, 130)}<b>Project ${i + 1}</b></div>`).join("")}</div><div class="card" style="margin-top:16px"><h3>Reviews</h3><p class="mu">“Great communication and always on time.” <span style="color:var(--pr)">★★★★★</span></p></div>`;
};

const oldProfile = null;
pages.profile = () =>
  S.viewEd || S.role == "editor" ? edProfile() : crProfile();
const edProfile =
  () => `<div class="cols" style="grid-template-columns:1fr 270px"><div><div class="card" style="padding:0;overflow:hidden"><div style="height:110px;${gv(0)}"></div><div style="padding:0 22px 22px"><div class="row wrap" style="align-items:flex-end;margin-top:-40px">${av("Sofia Reyes", 0, 1)}<div style="flex:1;padding-top:44px"><h2 style="margin:0">Sofia Reyes ${vb(1)}</h2><p class="mu">Video Editor · Motion Designer · Madrid</p></div></div><p style="margin:12px 0">Story-driven editor helping YouTubers turn raw footage into videos people finish. 6 years, 86 projects, 41 clients.</p><div class="grid stats" style="margin-bottom:14px">${[
    ["Projects", 86],
    ["Clients", 41],
    ["Videos", 214],
    ["Connections", 512],
  ]
    .map(
      (x) =>
        `<div><b style="font-size:20px">${x[1]}</b><div class="mu">${x[0]}</div></div>`,
    )
    .join(
      "",
    )}</div><div class="row wrap"><button class="btn" onclick="go('messages')">Message</button><button class="btn g" onclick="modal(hire('Sofia Reyes'))">Invite to Project</button><button class="btn g" onclick="go('services')">View Services</button></div></div></div>
<div class="card" style="margin-top:16px"><p class="mu">Specialized in</p><div style="margin:6px 0 12px">${["YouTube", "Short-form Content", "Podcast", "Motion Graphics", "Social Media"].map((s) => `<span class="badge">${s}</span> `).join("")}</div><p class="mu">Software</p><div style="margin:6px 0 12px">${["Premiere Pro", "After Effects", "DaVinci Resolve", "CapCut"].map((s) => `<span class="badge">${s}</span> `).join("")}</div><span>🟢 Available for work</span></div>
<div class="row sp" style="margin:22px 0 12px"><h2 style="margin:0">Portfolio</h2><a onclick="toast('Full portfolio')">See More</a></div><div class="grid">${["Documentary: The Last Fishers", "Tech Review Series", "Podcast Highlights", "Travel Vlog Ep. 12", "Brand Campaign 2026", "Gaming Montage"].map((t, i) => `<div class="card hv" style="cursor:pointer;padding:12px" onclick="modal(proj(${i}))">${th(i, 170)}<b>${t}</b><div class="mu">${["Documentary", "YouTube", "Podcast", "Vlog", "Brand", "Gaming"][i]} · ${["Northwind Films", "TechWave", "Loop Studio", "Wander", "Brightside", "Nova Plays"][i]}</div><p class="mu">Premiere Pro, DaVinci · Cinematic pacing and sound design.</p></div>`).join("")}</div></div>
<aside><div class="card"><h3>People you may know</h3>${[
    ["Omar Khalil", "Motion Designer"],
    ["Nina Park", "Colorist"],
  ]
    .map(
      (p) =>
        `<div class="row" style="margin-top:12px">${av(p[0])}<div style="flex:1"><b>${p[0]}</b><div class="mu">${p[1]}</div></div><a onclick="toast('Request sent')">Connect</a></div>`,
    )
    .join(
      "",
    )}</div><div class="card" style="margin-top:16px"><h3>Recent project</h3>${th(4, 80)}<b>Brand Campaign 2026</b><br><a onclick="modal(proj(4))">View Project</a></div><div class="card" style="margin-top:16px"><h3>Latest reviews</h3><p style="color:var(--pr)">★★★★★</p><p class="mu">“Great communication and…”</p><a>See all reviews</a></div><div class="card" style="margin-top:16px"><h3>Top skills</h3>${["Storytelling", "Sound design", "Pacing"].map((s) => `<span class="badge">${s}</span> `).join("")}<p style="margin-top:12px">🟢 Available</p></div></aside></div>`;
const crProfile = () => {
  const T = [
    "About",
    "Content",
    "Projects",
    "Posts",
    "Collaborations",
    "Network",
  ];
  return `<div class="card" style="padding:0;overflow:hidden"><div style="height:130px;${gv(1)}"></div><div style="padding:0 22px 22px"><div class="row wrap" style="align-items:flex-end;margin-top:-44px">${av("Abdullah Khan", "#111", 1)}<div style="flex:1;padding-top:48px"><h2 style="margin:0">Abdullah Khan ✔</h2><p class="mu">@abdullah · Amman, Jordan</p></div><button class="btn">Follow</button><button class="btn g" onclick="go('messages')">Message</button></div><p style="margin:12px 0">I make tech + education videos that explain AI in plain language for 12.8K curious builders. New episode every Friday.</p><p class="mu">🔗 youtube.com/@abdullah · 📸 @abdullah.creates · 🐦 @abdullah</p><div class="grid stats" style="margin-top:14px">${[
    ["Followers", "12.8K"],
    ["Following", 310],
    ["Connections", 286],
    ["Projects", 41],
    ["Videos", 128],
    ["Posts", 94],
  ]
    .map(
      (x) =>
        `<div><b style="font-size:20px">${x[1]}</b><div class="mu">${x[0]}</div></div>`,
    )
    .join("")}</div></div></div>
<div class="card" style="margin:16px 0"><h3>YouTube Creator · Tech + Education</h3><div style="margin-top:10px"><p class="mu">Primary content</p><span class="badge">YouTube</span><p class="mu" style="margin-top:8px">Content types</p>${["Long-form", "Short-form", "Podcast"].map((s) => `<span class="badge">${s}</span> `).join("")}<p class="mu" style="margin-top:8px">Topics</p>${["Technology", "Education", "AI"].map((s) => `<span class="badge">${s}</span> `).join("")}</div></div>${tabs(T, "pt")}${
    [
      `<div class="card"><h3>About</h3><p class="mu" style="margin-top:8px">Started in 2022 as a solo creator. Now collaborating with a small team of editors and designers to publish weekly. Style: calm, visual, story-first.</p></div>`,
      `<div class="grid">${[0, 1, 2, 3].map((i) => `<div class="card hv" style="padding:12px">${th(i, 130)}<b>${["How AI Actually Works", "Build a GPT in a day", "Study with me: 4hrs", "Shorts: 5 AI tools"][i]}</b><div class="mu">${["84K", "41K", "29K", "120K"][i]} views</div></div>`).join("")}</div>`,
      `<div class="grid">${MJ.filter((j) => j.k != "p")
        .slice(0, 4)
        .map(
          (j) =>
            `<div class="card hv">${th(j.gi, 90)}<b>${j.t}</b><div class="mu">with ${j.e}</div></div>`,
        )
        .join("")}</div>`,
      pages
        .community()
        .match(
          /<div class="card" style="margin-bottom:16px">[\s\S]*?<\/div><\/div>/,
        )
        ? POSTS.slice(0, 2)
            .map(
              (p) =>
                `<div class="card" style="margin-bottom:12px">${p[1]}</div>`,
            )
            .join("")
        : "",
      `<div class="grid">${["Sarah Ahmed", "Alex Morgan", "Lena Novak"].map((n) => `<div class="card row">${av(n)}<div><b>${n}</b><div class="mu">Collaborator</div></div></div>`).join("")}</div>`,
      `<div class="grid">${P.slice(0, 4)
        .map((p) => `<div class="card row">${av(p[0])}<b>${p[0]}</b></div>`)
        .join("")}</div>`,
    ][S.pt || 0]
  }`;
};

pages.explore = () =>
  `<h2>Explore</h2>${tabs(["Editors", "Projects", "Services", "Creators"], "tab")}<div class="row wrap" style="margin-bottom:16px">${["Specialization", "Software", "Content type", "Rating 4.5+", "Availability", "Price range"].map((f) => `<span class="tab">${f} ▾</span>`).join("")}</div><div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">${ED.map((e, i) => `<div class="card hv" style="padding:12px">${th(i, 210, "▶ Watch reel")}<div class="row">${av(e.n)}<div style="flex:1"><h3>${e.n} ${vb(e.v)}</h3><div class="mu">${e.s}</div></div><span>⭐ ${e.r}</span></div><p class="mu" style="margin:10px 0">${AP[i].bio}</p><div>${AP[i].sk.map((s) => `<span class="badge">${s}</span> `).join("")}</div><div class="row sp" style="margin-top:12px"><span class="mu">${["YouTube", "Motion", "Short-form", "Podcast"][i]} · from ${e.pr}</span><button class="btn s" onclick="vp()">View Profile</button></div></div>`).join("")}</div>`;

pages.network = () => {
  const R = [
    [
      "Nina Park",
      "Colorist & Editor",
      "Color-first storyteller for indie films.",
    ],
    [
      "Alex Morgan",
      "Motion Designer",
      "Titles and motion systems for creators.",
    ],
  ];
  const T = ["Suggested", "Requests (" + R.length + ")", "Connections"];
  return `<h2>Your Network</h2>${tabs(T, "tab")}<input placeholder="Search people, skills, roles…" style="margin-bottom:16px">${S.tab == 1 ? R.map((r) => `<div class="card req hv" style="margin-bottom:14px"><div class="row" style="flex-direction:column;align-items:stretch"><button class="btn s" onclick="toast('Connected with ${r[0]}')">Accept</button><button class="btn g s" onclick="toast('Declined')">Decline</button></div><div>${av(r[0]).replace('class="av"', 'class="av" style="width:72px;height:72px;font-size:22px"')}</div><div><h3>${r[0]} <span class="ver">✔</span></h3><div class="mu">${r[1]}</div><p style="margin:6px 0">${r[2]}</p><a onclick="vp()">View Profile</a></div></div>`).join("") : `<div class="grid">${P.map((p, i) => `<div class="card hv" style="padding:20px;overflow:hidden"><div class="ban" style="${gv(i)}"></div><div style="margin-top:-32px">${av(p[0]).replace('class="av"', 'class="av" style="width:64px;height:64px;font-size:20px;border:4px solid var(--card)"')}</div><h3 style="margin-top:8px">${p[0]} ${i < 4 ? '<span class="ver">✔</span>' : ""}</h3><div class="mu">${["Content Creator", "Video Editor", "Motion Designer", "YouTube Creator", "Social Media Manager", "Podcast Editor"][i]}</div><p class="mu" style="margin:8px 0">${["Weekly tech explainers.", "Story-driven edits.", "Motion for brands.", "Gaming & streaming.", "Campaigns that convert.", "Clean audio, sharp clips."][i]}</p><div class="row sp" style="margin-bottom:12px;font-size:13px"><span><b>${120 + i * 40}</b> connections</span><span><b>${10 + i * 7}</b> projects</span><span class="badge">${["YouTube", "Short", "Motion", "Gaming", "Social", "Podcast"][i]}</span></div><div class="row"><button class="btn s" onclick="this.textContent='Requested ✓'">Connect</button><button class="btn g s" onclick="vp()">View Profile</button></div></div>`).join("")}</div>`}`;
};

/* ===== v3: Admin subsystem + upgraded forms ===== */
S.area = "user";
S.arole = "verification";
S.apg = "dashboard";
const ROLES = {
  verification: {
    name: "Verification Specialist",
    nav: [
      ["dashboard", "🏠", "Dashboard"],
      ["verify", "✅", "Editor Verification"],
      ["cases", "📂", "Assigned Cases"],
      ["notif", "🔔", "Notifications"],
      ["profile", "👤", "Profile"],
    ],
  },
  moderator: {
    name: "Moderator",
    nav: [
      ["dashboard", "🏠", "Dashboard"],
      ["reports", "🚩", "Reports"],
      ["moderation", "🛡", "Content Moderation"],
      ["cases", "📂", "Assigned Cases"],
      ["notif", "🔔", "Notifications"],
      ["profile", "👤", "Profile"],
    ],
  },
  tech: {
    name: "Technical Support",
    nav: [
      ["dashboard", "🏠", "Dashboard"],
      ["tickets", "🎫", "Technical Tickets"],
      ["escalations", "⬆️", "Escalations"],
      ["cases", "📂", "Assigned Cases"],
      ["notif", "🔔", "Notifications"],
      ["profile", "👤", "Profile"],
    ],
  },
  cs: {
    name: "Customer Service",
    nav: [
      ["dashboard", "🏠", "Dashboard"],
      ["tickets", "🎫", "Customer Tickets"],
      ["chat", "💬", "Live Chat"],
      ["cases", "📂", "Assigned Cases"],
      ["escalations", "⬆️", "Escalations"],
      ["notif", "🔔", "Notifications"],
      ["profile", "👤", "Profile"],
    ],
  },
  manager: {
    name: "Manager",
    nav: [
      ["dashboard", "🏠", "Dashboard"],
      ["overview", "📊", "Department Overview"],
      ["escalations", "⬆️", "Escalations"],
      ["reports", "📈", "Reports"],
      ["team", "👥", "Team"],
      ["notif", "🔔", "Notifications"],
      ["profile", "👤", "Profile"],
    ],
  },
  correspondence: {
    name: "Correspondence Officer",
    nav: [
      ["dashboard", "🏠", "Dashboard"],
      ["diwan", "📜", "Official Correspondence"],
      ["notif", "🔔", "Notifications"],
      ["profile", "👤", "Profile"],
    ],
  },
};
const VCASES = [
  {
    id: "VER-2026-00128",
    n: "Noor Qassem",
    sp: "Short-form Editor",
    sub: "Oct 01",
    status: "Pending",
    exp: "3 yrs",
    port: 3,
  },
  {
    id: "VER-2026-00119",
    n: "Hassan Rami",
    sp: "Motion Designer",
    sub: "Sep 28",
    status: "Under Review",
    exp: "5 yrs",
    port: 5,
  },
  {
    id: "VER-2026-00104",
    n: "Yara Sami",
    sp: "YouTube Editor",
    sub: "Sep 20",
    status: "Additional Info",
    exp: "2 yrs",
    port: 2,
  },
  {
    id: "VER-2026-00091",
    n: "Karim Odeh",
    sp: "Podcast Editor",
    sub: "Sep 14",
    status: "Approved",
    exp: "6 yrs",
    port: 6,
  },
  {
    id: "VER-2026-00200",
    n: "Abdullah Khan",
    sp: "Video Editor & Motion Designer",
    sub: "Oct 05",
    status: "Under Review",
    exp: "6 yrs",
    port: 2,
    isDemoUser: 1,
  },
];
const REPORTS = [
  {
    id: "REP-2026-00291",
    by: "Maya Chen",
    target: "user_x82",
    reason: "Spam",
    st: "New",
    d: "Oct 03",
  },
  {
    id: "REP-2026-00284",
    by: "Jordan Ellis",
    target: "editor_nk19",
    reason: "Fake Profile",
    st: "Under Review",
    d: "Oct 02",
  },
  {
    id: "REP-2026-00277",
    by: "Lena Novak",
    target: "post_7731",
    reason: "Copyright",
    st: "Action Taken",
    d: "Sep 29",
  },
];
const TICKETS = [
  {
    id: "TEC-2026-00482",
    u: "Jordan Ellis",
    cat: "Portfolio Upload",
    pri: "High",
    st: "Escalated",
    c: "09:12",
    up: "11:45",
    tl: [
      ["10:32", "Ticket Created"],
      ["10:45", "Assigned to Junior Technical Support"],
      ["11:10", "Troubleshooting Started"],
      ["11:30", "User Provided Screenshot"],
      ["11:45", "Escalated to Senior Support"],
    ],
  },
  {
    id: "TEC-2026-00479",
    u: "Maya Chen",
    cat: "Login",
    pri: "Normal",
    st: "In Progress",
    c: "08:40",
    up: "09:05",
    tl: [
      ["08:40", "Ticket Created"],
      ["08:50", "Assigned to Junior Technical Support"],
      ["09:05", "Troubleshooting Started"],
    ],
  },
  {
    id: "TEC-2026-00471",
    u: "Studio Loop",
    cat: "Chat",
    pri: "Low",
    st: "Resolved",
    c: "Yesterday",
    up: "Yesterday",
    tl: [
      ["—", "Ticket Created"],
      ["—", "Resolved: cache cleared"],
    ],
  },
];
const CSTICKETS = [
  {
    id: "CUS-2026-00099",
    u: "Nina Park",
    topic: "How to post a job",
    st: "Open",
  },
  {
    id: "CUS-2026-00094",
    u: "Omar Haddad",
    topic: "How verification works",
    st: "Waiting for Reply",
  },
  {
    id: "CUS-2026-00088",
    u: "Studio Loop",
    topic: "Upload keeps failing",
    st: "Open",
    tech: 1,
  },
];
const DOCS = [
  {
    id: "DOC-2026-00281",
    subj: "Partnership agreement renewal",
    from: "Legal Dept",
    to: "Operations",
    st: "Pending Approval",
    d: "Oct 02",
  },
  {
    id: "DOC-2026-00275",
    subj: "Q3 compliance report",
    from: "Operations",
    to: "Management",
    st: "Approved",
    d: "Sep 25",
  },
  {
    id: "DOC-2026-00268",
    subj: "Vendor contract — hosting",
    from: "Finance",
    to: "Legal Dept",
    st: "Archived",
    d: "Aug 30",
  },
];
let AUDIT = [
  "Ahmed approved Editor Verification #VER-2026-00091",
  "Sarah escalated Technical Ticket #TEC-2026-00482 to Senior Support",
  "Moderator removed Post #7731 (Copyright)",
  "Manager approved Official Document #DOC-2026-00275",
];
const ANOTIF = {
  verification: [
    "New editor verification application — Noor Qassem",
    "Reminder: 2 applications pending > 48h",
  ],
  moderator: [
    "New report filed — Spam (user_x82)",
    "High-priority report requires review",
  ],
  tech: [
    "Technical ticket escalated to your level — TEC-2026-00482",
    "New ticket assigned: TEC-2026-00479",
  ],
  cs: [
    "Customer service ticket waiting for reply — CUS-2026-00094",
    "Ticket CUS-2026-00088 flagged as possibly technical",
  ],
  manager: [
    "Manager approval required — DOC-2026-00281",
    "Escalation awaiting review — TEC-2026-00482",
  ],
  correspondence: ["New document pending approval — DOC-2026-00281"],
};
const badge2 = (s) => `<span class="badge">${s}</span>`;
function adminOpen(k) {
  const allowed = ROLES[S.arole].nav.some((x) => x[0] == k);
  S.apg = allowed ? k : "restricted";
  S.rtried = k;
  draw();
}
const dashStats = {
  verification: [
    ["Pending", VCASES.filter((c) => c.status == "Pending").length],
    ["Under Review", VCASES.filter((c) => c.status == "Under Review").length],
    ["Approved", VCASES.filter((c) => c.status == "Approved").length],
    ["Need Info", VCASES.filter((c) => c.status == "Additional Info").length],
  ],
  moderator: [
    ["New Reports", REPORTS.filter((r) => r.st == "New").length],
    ["Under Review", REPORTS.filter((r) => r.st == "Under Review").length],
    ["Actions Taken", REPORTS.filter((r) => r.st == "Action Taken").length],
    ["Escalated", 0],
  ],
  tech: [
    ["Open Tickets", TICKETS.filter((t) => t.st != "Resolved").length],
    [
      "High Priority",
      TICKETS.filter((t) => t.pri == "High" || t.pri == "Critical").length,
    ],
    ["Waiting for User", 0],
    ["Escalated", TICKETS.filter((t) => t.st == "Escalated").length],
    ["Avg. Resolution", "4h 20m"],
  ],
  cs: [
    ["Open Conversations", CSTICKETS.filter((t) => t.st == "Open").length],
    [
      "Waiting for Reply",
      CSTICKETS.filter((t) => t.st == "Waiting for Reply").length,
    ],
    ["Resolved", 12],
    ["Escalated to Technical", CSTICKETS.filter((t) => t.tech).length],
  ],
  manager: [
    ["Dept. Performance", "92%"],
    ["Open Escalations", 2],
    ["SLA Status", "On Track"],
    ["Team Workload", "68%"],
  ],
  correspondence: [
    ["Drafts", DOCS.filter((d) => d.st == "Draft").length || 1],
    ["Pending Approval", DOCS.filter((d) => d.st == "Pending Approval").length],
    ["Approved", DOCS.filter((d) => d.st == "Approved").length],
    ["Archived", DOCS.filter((d) => d.st == "Archived").length],
  ],
};
function restricted() {
  return `<div class="card" style="max-width:460px;margin:60px auto;text-align:center;padding:40px"><div style="font-size:40px">🔒</div><h2>Access Restricted</h2><p class="mu" style="margin-top:8px">You don't have permission to access this section${S.rtried ? ' ("' + (ROLES[S.arole].nav.find((x) => x[0] == S.rtried) ? S.rtried : S.rtried) + '")' : ""}. Your role (${ROLES[S.arole].name}) only sees its own department.</p><button class="btn" style="margin-top:16px" onclick="adminOpen('dashboard')">Return to Dashboard</button></div>`;
}
function adminDash() {
  const s = dashStats[S.arole];
  return `<h2>Good morning, ${ROLES[S.arole].name} 👋</h2><p class="mu" style="margin-bottom:16px">Your department workspace — only sections for your role are shown.</p><div class="grid stats">${s.map((x) => stat(x[0], x[1])).join("")}</div>
<div class="row sp" style="margin:22px 0 10px"><h3>Recent activity</h3></div><div class="card">${AUDIT.slice(
    0,
    3,
  )
    .map(
      (a) =>
        `<p class="mu" style="padding:6px 0;border-bottom:1px solid var(--bd)">${a}</p>`,
    )
    .join("")}</div>
${S.arole == "verification" ? `<p class="mu" style="margin-top:18px">Demo only — try a section outside your role: <a onclick="adminOpen('tickets')">Technical Tickets</a></p>` : ""}`;
}
function caseCardV(c) {
  return `<div class="card hv"><div class="row sp"><span class="badge">${c.id}</span><span class="badge">${c.status}</span></div><div class="row" style="margin:10px 0">${av(c.n)}<div><b>${c.n}</b><div class="mu">${c.sp} · ${c.exp} · submitted ${c.sub}</div></div></div><p class="mu">${c.port} portfolio samples · Assigned: ${ROLES.verification.name}</p><div class="row wrap" style="margin-top:12px"><button class="btn s" onclick="modal(vdetail('${c.id}'))">Review</button></div></div>`;
}
function vdetail(id) {
  const c = VCASES.find((x) => x.id == id);
  return `<span class="badge">${c.id}</span><h2 style="margin:8px 0">${c.n} — ${c.sp}</h2><p class="mu">Submitted ${c.sub} · ${c.exp} experience · ${c.port} portfolio samples</p><div class="grid" style="margin:12px 0">${[
    0, 1, 2,
  ]
    .slice(0, c.port > 2 ? 3 : c.port)
    .map((i) => th(i, 80))
    .join(
      "",
    )}</div><label>Internal notes <span class="mu">(not visible to editor)</span></label><textarea rows=2 placeholder="e.g. Strong reel, needs one more long-form sample"></textarea><div class="row wrap" style="margin-top:14px"><button class="btn s" onclick="c.status='Approved';AUDIT.unshift('${ROLES.verification.name} approved Editor Verification #${c.id}');this.closest('.mod').remove();toast('Approved');draw()">Approve</button><button class="btn g s" onclick="c.status='Additional Info';this.closest('.mod').remove();toast('Requested more info');draw()">Request Information</button><button class="btn g s" onclick="c.status='Rejected';this.closest('.mod').remove();toast('Rejected');draw()">Reject</button><button class="btn g s" onclick="c.status='Suspended';this.closest('.mod').remove();toast('Suspended');draw()">Suspend</button></div>`;
}
function reportCard(r) {
  return `<div class="card hv"><div class="row sp"><span class="badge">${r.id}</span><span class="badge">${r.st}</span></div><p style="margin:10px 0 4px"><b>${r.reason}</b> on ${r.target}</p><p class="mu">Reported by ${r.by} · ${r.d}</p><div class="row wrap" style="margin-top:10px">${["Review", "Warn", "Remove Content", "Suspend", "No Action", "Escalate"].map((a) => `<button class="btn g s" onclick="r.st='Action Taken';AUDIT.unshift('${ROLES.moderator.name} ${a.toLowerCase() === "escalate" ? "escalated" : "actioned (" + a + ")"} Report #${r.id}');toast('${a} recorded');draw()">${a}</button>`).join("")}</div></div>`;
}
function ticketCard(t) {
  return `<div class="card hv"><div class="row sp"><span class="badge">${t.id}</span><span class="badge">${t.pri} · ${t.st}</span></div><p style="margin:10px 0 4px"><b>${t.cat}</b> — ${t.u}</p><p class="mu">Created ${t.c} · Updated ${t.up}</p><div class="row wrap" style="margin-top:10px"><button class="btn s" onclick="modal(tdetail('${t.id}'))">Open</button>${t.st != "Escalated" && t.st != "Resolved" ? `<button class="btn g s" onclick="modal(escalate('${t.id}'))">Escalate Case</button>` : ""}</div></div>`;
}
function tdetail(id) {
  const t = TICKETS.find((x) => x.id == id);
  return `<span class="badge">${t.id}</span><h2 style="margin:8px 0">${t.cat} — ${t.u}</h2><p class="mu">Priority ${t.pri} · Status ${t.st}</p><h3 style="margin-top:14px">Activity timeline</h3>${t.tl.map((e) => `<div class="row" style="padding:6px 0;border-bottom:1px solid var(--bd)"><b style="min-width:60px">${e[0]}</b><span class="mu">${e[1]}</span></div>`).join("")}<div class="row wrap" style="margin-top:14px">${t.st != "Resolved" ? `<button class="btn s" onclick="t.st='Resolved';this.closest('.mod').remove();toast('Marked resolved');draw()">Mark Resolved</button>` : ""}${t.st != "Escalated" && t.st != "Resolved" ? `<button class="btn g s" onclick="this.closest('.mod').remove();modal(escalate('${t.id}'))">Escalate Case</button>` : ""}</div>`;
}
function escalate(id) {
  return `<h2>Escalate Case ${id}</h2><p class="mu">Technical Support Junior → Senior → Supervisor → Manager</p><label>Escalation reason</label><input placeholder="e.g. Unable to reproduce the upload failure"><label>Problem summary</label><textarea rows=2 placeholder="What is happening and for whom"></textarea><label>Steps already taken</label><textarea rows=2 placeholder="Cleared cache, checked logs, reproduced on staging…"></textarea><label>Impact</label><input placeholder="e.g. Editor cannot submit verification portfolio"><label>Escalate to</label><select><option>Senior</option><option>Supervisor</option><option>Manager</option></select><div class="row" style="margin-top:14px"><button class="btn s" onclick="const t=TICKETS.find(x=>x.id=='${id}');if(t){t.st='Escalated';t.tl.push(['now','Escalated — notification sent']);}AUDIT.unshift('${ROLES[S.arole].name} escalated Technical Ticket #${id}');this.closest('.mod').remove();toast('Escalated — notified');draw()">Submit Escalation</button></div>`;
}
function csCard(t) {
  return `<div class="card hv"><div class="row sp"><span class="badge">${t.id}</span><span class="badge">${t.st}</span></div><p style="margin:10px 0 4px"><b>${t.topic}</b></p><p class="mu">${t.u}</p><div class="row wrap" style="margin-top:10px"><button class="btn s" onclick="toast('Reply sent')">Reply</button><button class="btn g s" onclick="t.st='Open';CSTICKETS.splice(CSTICKETS.indexOf(t),1);TICKETS.unshift({id:'TEC-2026-'+Math.floor(Math.random()*900+100),u:t.u,cat:'Other',pri:'Normal',st:'Open',c:'now',up:'now',tl:[['now','Transferred from Customer Service'],['now','Ticket Created']]});AUDIT.unshift('${ROLES.cs.name} transferred ticket ${t.id} to Technical Support');toast('Transferred — history kept');draw()">Transfer to Technical Support</button></div></div>`;
}
function docCard(doc) {
  return `<div class="card hv"><div class="row sp"><span class="badge">${doc.id}</span><span class="badge">${doc.st}</span></div><p style="margin:10px 0 4px"><b>${doc.subj}</b></p><p class="mu">${doc.from} → ${doc.to} · ${doc.d}</p><div class="row wrap" style="margin-top:10px">${doc.st == "Pending Approval" ? `<button class="btn s" onclick="doc.st='Approved';AUDIT.unshift('${ROLES.correspondence.name} approved Official Document #${doc.id}');toast('Approved');draw()">Approve</button>` : ""}${doc.st == "Approved" ? `<button class="btn s" onclick="doc.st='Sent';toast('Sent');draw()">Send</button>` : ""}<button class="btn g s" onclick="toast('Opened '+doc.id)">Open</button></div></div>`;
}
const admin = {
  dashboard: adminDash,
  verify: () =>
    `<h2>Editor Verification</h2>${tabs(["Pending", "Under Review", "Additional Info", "Approved", "Rejected"], "avt")}<div class="grid">${
      VCASES.filter(
        (c) =>
          c.status ==
          [
            "Pending",
            "Under Review",
            "Additional Info",
            "Approved",
            "Rejected",
          ][S.avt || 0],
      )
        .map(caseCardV)
        .join("") || '<div class="card mu">No cases in this status.</div>'
    }</div>`,
  cases: () =>
    `<h2>My Assigned Cases</h2><div class="grid">${(S.arole == "verification" ? VCASES.map(caseCardV) : S.arole == "moderator" ? REPORTS.map(reportCard) : S.arole == "tech" ? TICKETS.map(ticketCard) : S.arole == "cs" ? CSTICKETS.map(csCard) : ['<div class="card mu">No cases assigned.</div>']).join("")}</div>`,
  reports: () =>
    `<h2>Reports</h2><div class="grid">${REPORTS.map(reportCard).join("")}</div>`,
  moderation: () =>
    `<h2>Content Moderation</h2><p class="mu" style="margin-bottom:14px">Reported posts and users awaiting action.</p><div class="grid">${REPORTS.map(reportCard).join("")}</div>`,
  tickets: () =>
    `<h2>${S.arole == "cs" ? "Customer Tickets" : "Technical Tickets"}</h2><div class="grid">${(S.arole == "cs" ? CSTICKETS.map(csCard) : TICKETS.map(ticketCard)).join("")}</div>`,
  chat: () =>
    `<h2>Live Chat</h2><div class="card chat" style="grid-template-columns:220px 1fr"><div>${CSTICKETS.map((t) => `<div class="row" style="padding:8px 0">${av(t.u)}<div><b>${t.u}</b><div class="mu">${t.topic}</div></div></div>`).join("")}</div><div style="display:flex;flex-direction:column"><div style="flex:1"><div class="bub">Hi, how do I post a job?</div><div class="bub me">Happy to help — go to Dashboard → Post a Job.</div></div><div class="row"><input placeholder="Type a reply…"><button class="btn s" onclick="toast('Sent')">Send</button></div></div></div>`,
  escalations: () =>
    `<h2>Escalations</h2><div class="grid">${
      TICKETS.filter((t) => t.st == "Escalated")
        .map(ticketCard)
        .join("") || '<div class="card mu">No open escalations.</div>'
    }</div>`,
  diwan: () =>
    `<h2>Official Correspondence</h2>${tabs(["Incoming", "Outgoing", "Drafts", "Archive"], "dt")}<div class="grid">${DOCS.map(docCard).join("")}</div><button class="btn g" style="margin-top:14px" onclick="modal('<h2>New Document</h2><label>Subject</label><input><label>Recipient department</label><input><label>Priority</label><select><option>Normal</option><option>High</option></select><button class=\\'btn s\\' style=\\'margin-top:14px\\' onclick=\\'this.closest(\".mod\").remove();toast(\"Draft saved\")\\'>Save Draft</button>')">+ New Document</button>`,
  overview: () =>
    `<h2>Department Overview</h2><div class="grid">${Object.keys(ROLES)
      .filter((r) => r != "manager")
      .map(
        (r) =>
          `<div class="card"><h3>${ROLES[r].name}</h3>${dashStats[r]
            .slice(0, 3)
            .map(
              (x) =>
                `<div class="row sp" style="padding:4px 0"><span class="mu">${x[0]}</span><b>${x[1]}</b></div>`,
            )
            .join("")}</div>`,
      )
      .join("")}</div>`,
  team: () =>
    `<h2>Team Workload</h2><div class="grid">${["Ahmed — Verification", "Sarah — Technical", "Lina — Moderation", "Omar — Customer Service"].map((n) => `<div class="card"><div class="row">${av(n.split(" — ")[0])}<div><b>${n.split(" — ")[0]}</b><div class="mu">${n.split(" — ")[1]}</div></div></div><div class="prog" style="margin-top:10px"><i style="width:${(50 + Math.random() * 40) | 0}%"></i></div></div>`).join("")}</div>`,
  notif: () =>
    `<h2>Notifications</h2><div class="card">${(ANOTIF[S.arole] || []).map((n) => `<p style="padding:10px 0;border-bottom:1px solid var(--bd)">${n}</p>`).join("")}</div>`,
  profile: () =>
    `<h2>Admin Profile</h2><div class="card row">${av("Admin User", "#111")}<div><b>Admin User</b><div class="mu">${ROLES[S.arole].name}</div></div></div>`,
  restricted: restricted,
};
function renderAdmin() {
  const nav = ROLES[S.arole].nav;
  return `<div class="app"><nav class="side"><div class="logo">Fra<b>me</b> <span class="mu" style="font-size:12px">Admin</span></div><select style="margin-bottom:12px" onchange="S.arole=this.value;adminOpen('dashboard')">${Object.keys(
    ROLES,
  )
    .map(
      (r) =>
        `<option value="${r}" ${S.arole == r ? "selected" : ""}>${ROLES[r].name}</option>`,
    )
    .join(
      "",
    )}</select>${nav.map((x) => `<div class="ni ${S.apg == x[0] ? "on" : ""}" onclick="adminOpen('${x[0]}')">${x[1]} ${x[2]}</div>`).join("")}<div class="ni" onclick="S.area='user';go('landing')">🚪 Logout</div></nav><div><div class="top"><b>${ROLES[S.arole].name}</b><span style="cursor:pointer;margin-left:auto" onclick="adminOpen('notif')">🔔 ${(ANOTIF[S.arole] || []).length}</span><span style="cursor:pointer" onclick="theme();draw()">◐</span></div><div class="main">${(admin[S.apg] || admin.dashboard)()}</div></div></div><div class="mob">${nav
    .slice(0, 5)
    .map(
      (x) =>
        `<span class="${S.apg == x[0] ? "on" : ""}" onclick="adminOpen('${x[0]}')"><b>${x[1]}</b>${x[2].split(" ")[0]}</span>`,
    )
    .join("")}</div>`;
}
pages.adminlogin = () =>
  `<div style="min-height:100vh;display:grid;place-items:center;padding:16px"><div class="card" style="max-width:640px;width:100%"><div class="logo">Fra<b>me</b> <span class="mu" style="font-size:13px">Admin</span></div><h2>Sign in as</h2><p class="mu" style="margin-bottom:14px">Each role only sees its own department — pick one to preview.</p><div class="grid">${Object.keys(
    ROLES,
  )
    .map(
      (r) =>
        `<div class="card hv" style="cursor:pointer;padding:16px" onclick="S.arole='${r}';S.area='admin';S.apg='dashboard';go('admin')"><b>${ROLES[r].name}</b><p class="mu" style="margin-top:6px">${ROLES[
          r
        ].nav
          .slice(1, -3)
          .map((x) => x[2])
          .join(", ")}</p></div>`,
    )
    .join(
      "",
    )}</div><p class="mu" style="margin-top:14px"><a onclick="go('landing')">← Back to site</a></p></div></div>`;
pages.admin = () => "";

/* ===== Upgraded Editor Verification form ===== */
pages.verify = function () {
  const st = [
    "Personal",
    "Professional",
    "Specializations",
    "Software",
    "Experience",
    "Portfolio",
    "Availability",
    "Review",
  ];
  if (S.step > 7)
    return `<div class="card" style="max-width:520px;margin:auto;text-align:center;padding:40px"><div style="font-size:44px">📨</div><h2>Application Submitted</h2><span class="badge">${S.needInfo ? "Additional Information Required" : "Under Review"}</span><p class="mu" style="margin-top:12px">${S.needInfo ? "Please add one more long-form editing sample. You can update just this section — no need to restart." : "We'll email you within 5 business days. Verification reflects a profile & portfolio review, not a guarantee of project outcomes."}</p>${S.needInfo ? `<button class="btn" style="margin-top:14px" onclick="S.step=5;draw()">Update Portfolio</button>` : `<button class="btn g" style="margin-top:14px" onclick="S.needInfo=1;draw()">Demo: request more info</button>`}</div>`;
  const n = S.step;
  const req = (w) => `<span class="mu" style="font-size:11px"> ${w}</span>`;
  const body = [
    `<label>Full Name ${req("Required")}</label><input value="Abdullah Khan"><label>Display Name</label><input value="Abdullah K."><label>Profile Photo ${req("Required")}</label><div class="card" style="border-style:dashed;text-align:center;padding:14px">Upload photo</div><div class="row"><div><label>Country</label><input value="Jordan"></div><div><label>City / Region</label><input value="Amman"></div></div><label>Languages</label><input value="Arabic, English"><label>Timezone</label><select><option>GMT+3 Amman</option></select>`,
    `<label>Professional Title ${req("Required")}</label><input value="Video Editor & Motion Designer"><label>Short Bio ${req("Required")}</label><textarea rows=3 placeholder="e.g. I help YouTubers turn raw footage into videos people finish">Story-driven editor, 6 years, 86 projects.</textarea><label>Years of Experience</label><input value="6"><label>Work Type</label><select>${["Freelancer", "Part-time", "Full-time", "Agency", "Student"].map((o) => `<option>${o}</option>`).join("")}</select>`,
    `<label>Content Types ${req("select a few")}</label><div class="row wrap">${["YouTube", "TikTok", "Instagram Reels", "Podcasts", "Ads", "Gaming", "Education", "Documentary", "Corporate", "Personal Brand"].map((c, i) => `<span class="tab ${i < 3 ? "on" : ""}" onclick="this.classList.toggle('on')">${c}</span>`).join("")}</div><label style="margin-top:14px">Skills</label><div class="row wrap">${["Storytelling", "Color Grading", "Sound Design", "Motion Graphics", "Subtitles", "VFX", "Short-form Editing"].map((c, i) => `<span class="tab ${i < 2 ? "on" : ""}" onclick="this.classList.toggle('on')">${c}</span>`).join("")}</div>`,
    `<p class="mu">Select your tools and self-reported skill level. ${req("Self-reported — not the same as verification")}</p>${["Premiere Pro", "After Effects", "DaVinci Resolve", "Final Cut Pro", "CapCut", "Photoshop", "Illustrator", "Blender"].map((sw) => `<div class="row sp" style="padding:6px 0"><span>${sw}</span><select style="width:150px">${["—", "Beginner", "Intermediate", "Advanced", "Expert"].map((l) => `<option ${l == "Advanced" && sw == "Premiere Pro" ? "selected" : ""}>${l}</option>`).join("")}</select></div>`).join("")}`,
    `<label>Years of Experience</label><input value="6"><label>Completed Projects</label><input value="86"><label>Previous Client Experience</label><textarea rows=2 placeholder="e.g. Worked with 3 YouTube channels (100K+ subs)">Worked with indie documentary studios and 3 YouTube channels.</textarea><label>Main Industries</label><input value="YouTube, Documentary, Education"><label>Portfolio Link ${req("Optional")}</label><input placeholder="https://yourportfolio.com">`,
    `<p class="mu" style="margin-bottom:10px">Add at least 2 projects — this is the most important section.</p>${Array.from(
      { length: S.pfc || 2 },
    )
      .map(
        (_, i) =>
          `<div class="card" style="margin-bottom:12px"><b>Project ${i + 1}</b><label>Project Title ${req("Required")}</label><input placeholder="e.g. Documentary: The Last Fishers"><div class="row"><div><label>Content Type</label><input placeholder="Documentary"></div><div><label>Editor Role</label><input placeholder="Lead editor"></div></div><label>Client Type</label><input placeholder="Indie studio"><label>Description</label><textarea rows=2 placeholder="What you did and the result"></textarea><label>Tools Used</label><input placeholder="Premiere, DaVinci"><label>Project Link ${req("Optional")}</label><input placeholder="https://…"><label>Thumbnail</label><div class="card" style="border-style:dashed;text-align:center;padding:10px">Upload thumbnail</div></div>`,
      )
      .join(
        "",
      )}<button class="btn g s" onclick="S.pfc=(S.pfc||2)+1;draw()">+ Add Another Project</button>`,
    `<label>Availability</label><div class="row wrap">${["Available", "Limited Availability", "Not Available"].map((a, i) => `<span class="tab ${i == 0 ? "on" : ""}" onclick="document.querySelectorAll('.tab').forEach(t=>t.classList.remove('on'));this.classList.add('on')">${a}</span>`).join("")}</div><label>Hours per week</label><input value="25"><label>Typical delivery time</label><input value="4–5 days"><label>Current workload</label><select><option>Light</option><option selected>Moderate</option><option>Heavy</option></select>`,
    `<div class="card" style="background:var(--pl);border:0"><span class="badge">Review</span><h3 style="margin:8px 0">Abdullah Khan — Video Editor & Motion Designer</h3><p class="mu">Personal ✔ · Professional ✔ · Skills ✔ · Software ✔ · Experience ✔ · Portfolio (${S.pfc || 2} projects) ✔ · Availability ✔</p></div>`,
  ][n];
  return `<div style="max-width:640px;margin:auto"><h2>Become a Verified Editor</h2><p class="mu">Verified editors have completed our profile and portfolio review process.</p><p class="mu" style="margin:10px 0 4px">Step ${n + 1}/8 — ${st[n]}</p><div class="prog"><i style="width:${((n + 1) * 100) / 8}%"></i></div><div class="card" style="margin-top:14px">${body}<div class="row sp" style="margin-top:20px"><button class="btn g" ${n ? "" : "disabled"} onclick="S.step--;draw()">Back</button><div class="row">${n < 7 ? `<button class="btn g" onclick="toast('Draft saved')">Save Draft</button><button class="btn" onclick="S.step++;draw()">Next</button>` : `<button class="btn" onclick="toast('Submitted for verification 🎉');S.step=8;draw()">Submit for Verification</button>`}</div></div></div></div>`;
};

/* ===== Upgraded Post a Job form ===== */
pages.postjob = function () {
  const st = [
    "Basics",
    "Description",
    "Requirements",
    "Software",
    "Deliverables",
    "Budget",
    "Timeline",
    "Preferences",
    "Review",
  ];
  const n = S.step || 0;
  const opt = (w) => `<span class="mu" style="font-size:11px"> ${w}</span>`;
  const body = [
    `<label>Job Title ${opt("Required")}</label><input value="YouTube Series — Episode Editing"><label>Content Type</label><select>${["YouTube", "Shorts", "Reels", "TikTok", "Podcast", "Gaming", "Ads", "Other"].map((o) => `<option>${o}</option>`).join("")}</select><label>Quantity</label><input value="8 videos">`,
    `<label>What are you creating? ${opt("Required")}</label><textarea rows=2 placeholder="e.g. A tech-explainer series for our YouTube channel">8 episodes of a tech-explainer series.</textarea><label>What should the editor do?</label><textarea rows=2 placeholder="e.g. Cut raw footage into a tight 10–14 min episode with b-roll and captions"></textarea><label>What style are you looking for?</label><input placeholder="e.g. Fast-paced, punchy"><label>Reference links ${opt("Optional")}</label><input placeholder="https://youtube.com/…">`,
    `<div class="row"><div><label>Video Length</label><input value="10–14 min"></div><div><label>Editing Style</label><select>${["Fast-paced", "Cinematic", "Minimal", "Documentary", "Educational", "Social Media"].map((o) => `<option>${o}</option>`).join("")}</select></div></div><label>Required Skills</label><div class="row wrap">${["Motion Graphics", "Color Grading", "Sound Design", "Subtitles", "VFX", "Storytelling"].map((c, i) => `<span class="tab ${i < 2 ? "on" : ""}" onclick="this.classList.toggle('on')">${c}</span>`).join("")}</div><label style="margin-top:12px">Experience Level</label><select><option>Any</option><option selected>Intermediate+</option><option>Expert only</option></select>`,
    `<p class="mu" style="margin-bottom:8px">No need to understand technical specifics — pick a preference if you have one.</p><select>${["No Preference", "Premiere Pro", "After Effects", "DaVinci Resolve", "Final Cut Pro", "CapCut"].map((o) => `<option>${o}</option>`).join("")}</select>`,
    `<label>Deliverables ${opt("Required")}</label><input placeholder="e.g. 8 edited videos" value="8 edited videos"><label>File Format</label><select><option>MP4</option><option>MOV</option></select><label>Resolution</label><select><option>1080p</option><option>4K</option></select><label>Number of Revisions</label><input value="2"><label>Additional Requirements ${opt("Optional")}</label><textarea rows=2 placeholder="e.g. Include captions burned in"></textarea>`,
    `<label>Budget Type</label><select>${["Fixed", "Per Video", "Monthly", "Hourly", "Negotiable"].map((o) => `<option>${o}</option>`).join("")}</select><div class="row"><div><label>Minimum</label><input value="$1,500"></div><div><label>Maximum</label><input value="$2,000"></div></div>`,
    `<div class="row"><div><label>Start Date</label><input type=date></div><div><label>Deadline</label><input type=date></div></div><label>Recurring Project?</label><select onchange="S.rec=this.value;draw()"><option value=0 ${!S.rec ? "selected" : ""}>No</option><option value=1 ${S.rec ? "selected" : ""}>Yes</option></select>${S.rec ? `<label>Frequency</label><select><option>Weekly</option><option>Biweekly</option><option>Monthly</option></select>` : ""}`,
    `<label>Experience Level</label><select><option>Any</option><option selected>Intermediate+</option></select><label>Availability</label><select><option>Any</option><option selected>Available now</option></select><div class="row sp" style="margin-top:14px;padding:12px;background:var(--pl);border-radius:12px"><div><b>Verified Editors Only</b><div class="mu" style="font-size:12px">Recommended</div></div><input type=checkbox checked style="width:auto" onchange="S.veOnly=this.checked"></div>`,
    `<div class="card" style="background:var(--pl);border:0"><span class="badge">Preview</span><h3 style="margin:8px 0">YouTube Series — Episode Editing</h3><p class="mu">8 videos · Premiere, DaVinci ok · $1,500–$2,000 · 1080p, MP4 · 2 revisions · ${S.veOnly === false ? "Open to all" : "Verified editors only"}</p></div>`,
  ][n];
  return `<div style="max-width:640px;margin:auto"><h2>Post a Job</h2><p class="mu" style="margin:6px 0 4px">Step ${n + 1}/9 — ${st[n]}</p><div class="prog"><i style="width:${((n + 1) * 100) / 9}%"></i></div><div class="card" style="margin-top:14px">${body}<div class="row sp" style="margin-top:20px"><button class="btn g" ${n ? "" : "disabled"} onclick="S.step--;draw()">Back</button><div class="row">${n < 8 ? `<button class="btn g" onclick="toast('Draft saved')">Save Draft</button><button class="btn" onclick="S.step++;draw()">Next</button>` : `<button class="btn g" onclick="toast('Draft saved')">Save Draft</button><button class="btn" onclick="toast('Job published 🎉');go('jobs')">Publish Job</button>`}</div></div></div></div>`;
};

/* ===== v4: 3-role admin model (Admin 1, Admin 2, Customer Support) ===== */
const ask = (t, fn) => {
  modal(
    `<h2>Please confirm</h2><p class="mu">${t}</p><div class="row" style="margin-top:16px"><button class="btn s" id="okb">Confirm</button><button class="btn g s" onclick="this.closest('.mod').remove()">Cancel</button></div>`,
  );
  const b = $("#okb");
  b.onclick = () => {
    b.closest(".mod").remove();
    fn();
  };
};
Object.keys(ROLES).forEach((k) => delete ROLES[k]);
const adminNav = [
  ["dashboard", "🏠", "Dashboard"],
  ["users", "👥", "Users"],
  ["verify", "✅", "Editor Verification"],
  ["moderation", "🛡", "Moderation"],
  ["escalations", "⬆️", "Escalations"],
  ["settings", "⚙️", "Platform Settings"],
  ["notif", "🔔", "Notifications"],
  ["profile", "👤", "Profile"],
];
ROLES.admin1 = { name: "Admin 1", nav: adminNav };
ROLES.admin2 = { name: "Admin 2", nav: adminNav };
ROLES.support = {
  name: "Customer Support",
  nav: [
    ["dashboard", "🏠", "Dashboard"],
    ["tickets", "🎫", "Tickets"],
    ["followup", "📨", "Verification Follow-up"],
    ["notif", "🔔", "Notifications"],
    ["profile", "👤", "Profile"],
  ],
};
S.arole = "admin1";
S.avt = 0;
S.fvt = 0;
S.tkt = 0;
const who = () => ROLES[S.arole].name,
  isAdm = () => S.arole != "support",
  log = (a) => {
    AUDIT.unshift(who() + " " + a);
  };
const USERS = [
  { id: "U-1001", n: "Maya Chen", t: "Creator", st: "Active" },
  { id: "U-1002", n: "Sofia Reyes", t: "Editor", st: "Active" },
  { id: "U-1003", n: "Omar Haddad", t: "Editor", st: "Active" },
  { id: "U-1004", n: "user_x82", t: "Creator", st: "Suspended" },
  { id: "U-1005", n: "Nina Park", t: "Editor", st: "Active" },
  { id: "U-1006", n: "Jordan Ellis", t: "Creator", st: "Active" },
];
const SUPT = [
  {
    id: "CUS-2026-00099",
    u: "Nina Park",
    cat: "Jobs and applications",
    pri: "Normal",
    st: "Open",
    sub: "How do I post a job?",
    tl: ["Ticket created"],
  },
  {
    id: "CUS-2026-00094",
    u: "Omar Haddad",
    cat: "Editor verification follow-up",
    pri: "Normal",
    st: "Awaiting User",
    sub: "What does verification require?",
    tl: ["Ticket created", "Requested more information"],
  },
  {
    id: "CUS-2026-00088",
    u: "Studio Loop",
    cat: "Registration and account access",
    pri: "High",
    st: "In Progress",
    sub: "Cannot log in after reset",
    tl: ["Ticket created", "Reply sent: please try the new link"],
  },
  {
    id: "CUS-2026-00081",
    u: "Jordan Ellis",
    cat: "Payments and payment inquiries",
    pri: "High",
    st: "Escalated",
    sub: "Duplicate charge on project",
    tl: ["Ticket created", "Escalated to Admin: needs refund authority"],
  },
];
Object.keys(ANOTIF).forEach((k) => delete ANOTIF[k]);
Object.assign(ANOTIF, {
  admin1: [
    "Ticket escalated — CUS-2026-00081",
    "Verification ready for decision — Hassan Rami",
    "New moderation report — Spam",
  ],
  admin2: [
    "Ticket escalated — CUS-2026-00081",
    "Verification ready for decision — Hassan Rami",
    "New moderation report — Spam",
  ],
  support: [
    "New customer ticket — CUS-2026-00099",
    "Waiting for reply — CUS-2026-00094",
    "Verification needs follow-up — Noor Qassem",
  ],
});
const SETT = { name: "Frame", maxMb: 500, signups: true, minPort: 3 };
const open_ = ["Open", "In Progress"],
  done_ = ["Resolved", "Closed"];

function adminDash() {
  const sup = !isAdm();
  const st = sup
    ? [
        ["Open Tickets", SUPT.filter((t) => open_.includes(t.st)).length],
        ["Awaiting User", SUPT.filter((t) => t.st == "Awaiting User").length],
        [
          "High Priority",
          SUPT.filter(
            (t) =>
              ["High", "Critical"].includes(t.pri) && !done_.includes(t.st),
          ).length,
        ],
        [
          "Verification Follow-up",
          VCASES.filter((c) =>
            ["Pending", "Additional Info"].includes(c.status),
          ).length,
        ],
        ["Escalated", SUPT.filter((t) => t.st == "Escalated").length],
      ]
    : [
        ["Total Users", USERS.length],
        ["Creators", USERS.filter((u) => u.t == "Creator").length],
        ["Editors", USERS.filter((u) => u.t == "Editor").length],
        [
          "Verification Decisions",
          VCASES.filter((c) => c.status == "Under Review").length,
        ],
        ["Open Escalations", SUPT.filter((t) => t.st == "Escalated").length],
        [
          "Pending Moderation",
          REPORTS.filter((r) => ["New", "Under Review"].includes(r.st)).length,
        ],
      ];
  const links = sup
    ? [
        ["tickets", "Tickets"],
        ["followup", "Verification Follow-up"],
      ]
    : [
        ["users", "Users"],
        ["verify", "Verification"],
        ["moderation", "Moderation"],
        ["escalations", "Escalations"],
        ["settings", "Settings"],
      ];
  const locked = ["users", "settings", "verify", "tickets"].find(
    (k) => !ROLES[S.arole].nav.some((x) => x[0] == k),
  );
  return `<h2>${who()} workspace</h2><p class="mu" style="margin-bottom:14px">Mock data for the prototype.</p><div class="row wrap" style="margin-bottom:16px">${links.map((l) => `<span class="tab" onclick="adminOpen('${l[0]}')">${l[1]}</span>`).join("")}</div><div class="grid stats">${st.map((x) => stat(x[0], x[1])).join("")}</div><h3 style="margin:22px 0 10px">Recent activity</h3><div class="card">${AUDIT.slice(
    0,
    4,
  )
    .map(
      (a) =>
        `<p class="mu" style="padding:6px 0;border-bottom:1px solid var(--bd)">${a}</p>`,
    )
    .join(
      "",
    )}</div>${locked ? `<p class="mu" style="margin-top:16px">Demo: try a section outside your role — <a onclick="adminOpen('${locked}')">open ${locked}</a></p>` : ""}`;
}

const vBtns = (c, m) => {
  if (m == "adm")
    return c.status == "Under Review"
      ? `<button class="btn s" onclick="vAct('${c.id}','approve')">Approve</button><button class="btn g s" onclick="vAct('${c.id}','reject')">Reject</button>`
      : "";
  if (c.status == "Pending")
    return `<button class="btn s" onclick="vAct('${c.id}','fwd')">Complete → send to Admin</button><button class="btn g s" onclick="vAct('${c.id}','info')">Request missing info</button>`;
  if (c.status == "Additional Info")
    return `<button class="btn s" onclick="vAct('${c.id}','recheck')">Applicant responded</button>`;
  if (["Approved", "Rejected"].includes(c.status))
    return `<button class="btn g s" onclick="vAct('${c.id}','notify')">Notify applicant</button>`;
  return '<span class="mu">With Admin for decision</span>';
};
const vcard = (c, m) =>
  `<div class="card hv"><div class="row sp"><span class="badge">${c.id}</span><span class="badge">${c.status}</span></div><div class="row" style="margin:10px 0">${av(c.n)}<div><b>${c.n}</b><div class="mu">${c.sp} · ${c.exp} · submitted ${c.sub} · ${c.port} samples</div></div></div>${m == "adm" && c.status == "Under Review" ? `<label>Note <span class="mu">— Internal, not visible to editor</span></label><input id="n-${c.id}" placeholder="Reason / note">` : ""}<div class="row wrap" style="margin-top:10px">${vBtns(c, m)}</div></div>`;
function vAct(id, a) {
  const c = VCASES.find((x) => x.id == id),
    n = ((document.getElementById("n-" + id) || {}).value || "").trim();
  const fin = (s, msg) => {
    c.status = s;
    log(msg + " Editor Verification #" + id + (n ? " — " + n : ""));
    toast(msg);
    draw();
  };
  if (a == "approve")
    ask("Approve " + c.n + "? The editor gets a verified badge.", () =>
      fin("Approved", "approved"),
    );
  else if (a == "reject")
    ask("Reject " + c.n + "? The applicant will be informed.", () =>
      fin("Rejected", "rejected"),
    );
  else if (a == "fwd") {
    ANOTIF.admin1.unshift("Verification ready for decision — " + c.n);
    ANOTIF.admin2.unshift("Verification ready for decision — " + c.n);
    fin("Under Review", "forwarded to Admin:");
  } else if (a == "info") {
    if (c.isDemoUser) S.needInfo = 1;
    fin("Additional Info", "requested missing info for");
  } else if (a == "recheck") fin("Pending", "rechecked");
  else {
    log("notified applicant about decision on #" + id);
    toast("Applicant notified");
  }
}
const grid = (h) =>
  `<div class="grid">${h || '<div class="card mu">Nothing here.</div>'}</div>`;

const tcard = (t) =>
  `<div class="card hv"><div class="row sp"><span class="badge">${t.id}</span><span class="badge">${t.pri} · ${t.st}</span></div><p style="margin:10px 0 4px"><b>${t.sub}</b></p><p class="mu">${t.u} · ${t.cat}</p><button class="btn s" style="margin-top:10px" onclick="modal(tdet('${t.id}'))">Open</button></div>`;
function tdet(id) {
  const t = SUPT.find((x) => x.id == id),
    b = (a, l, g) =>
      `<button class="btn ${g ? "g " : ""}s" onclick="tAct('${id}','${a}')">${l}</button>`,
    cl = done_.includes(t.st);
  let acts = "";
  if (isAdm())
    acts =
      t.st == "Escalated"
        ? b("resolve", "Resolve") + b("ret", "Return to Support", 1)
        : '<span class="mu">Not escalated — handled by Customer Support</span>';
  else
    acts =
      t.st == "Escalated"
        ? '<span class="mu">Escalated — awaiting Admin</span>'
        : cl
          ? (t.st == "Resolved" ? b("close", "Close Ticket") : "") +
            b("reopen", "Reopen", 1)
          : b("reply", "Reply") +
            b("info", "Request Info", 1) +
            b("resolve", "Resolve", 1) +
            b("esc", "Escalate to Admin", 1);
  return `<span class="badge">${t.id}</span><h2 style="margin:8px 0">${t.sub}</h2><p class="mu">${t.u} · ${t.cat} · ${t.pri}</p><h3 style="margin-top:12px">Activity</h3>${t.tl.map((e) => `<div class="mu" style="padding:5px 0;border-bottom:1px solid var(--bd)">${e}</div>`).join("")}<div class="row wrap" style="margin-top:14px">${acts}</div>`;
}
function tAct(id, a) {
  if (a == "reply" || a == "esc") {
    document.querySelectorAll(".mod").forEach((x) => x.remove());
    modal(
      `<h2>${a == "reply" ? "Reply to user" : "Escalate to Admin"}</h2><label>${a == "reply" ? "Message" : "Reason and what was already tried"}</label><textarea id="tv" rows=3></textarea><button class="btn s" style="margin-top:12px" onclick="tDo('${id}','${a}')">${a == "reply" ? "Send" : "Escalate"}</button>`,
    );
    return;
  }
  tDo(id, a);
}
function tDo(id, a) {
  const t = SUPT.find((x) => x.id == id),
    v = ((document.getElementById("tv") || {}).value || "").trim();
  if ((a == "reply" || a == "esc") && !v) {
    toast("Please write a message first");
    return;
  }
  const m = {
    reply: [t.st == "Open" ? "In Progress" : t.st, "Reply sent: " + v],
    info: ["Awaiting User", "Requested more information"],
    resolve: ["Resolved", "Marked resolved by " + who()],
    close: ["Closed", "Closed"],
    reopen: ["Open", "Reopened"],
    esc: ["Escalated", "Escalated to Admin: " + v],
    ret: ["In Progress", "Returned to Customer Support"],
  }[a];
  t.st = m[0];
  t.tl.push(m[1]);
  log(
    {
      esc: "escalated",
      resolve: "resolved",
      close: "closed",
      reopen: "reopened",
      ret: "returned",
      reply: "replied on",
      info: "requested info on",
    }[a] +
      " Ticket #" +
      id,
  );
  if (a == "esc") {
    ANOTIF.admin1.unshift("Ticket escalated — " + id);
    ANOTIF.admin2.unshift("Ticket escalated — " + id);
  }
  document.querySelectorAll(".mod").forEach((x) => x.remove());
  toast("Updated");
  draw();
}
function newTicket() {
  modal(
    `<h2>New ticket</h2><label>User</label><input id="nu" placeholder="e.g. Maya Chen"><label>Category</label><select id="nc">${["Registration and account access", "Editor verification follow-up", "Jobs and applications", "Projects and deliverables", "Payments and payment inquiries", "General questions", "Other"].map((c) => `<option>${c}</option>`).join("")}</select><label>Subject</label><input id="ns"><label>Priority</label><select id="np"><option>Low</option><option selected>Normal</option><option>High</option><option>Critical</option></select><button class="btn s" style="margin-top:12px" onclick="addTicket()">Create</button>`,
  );
}
function addTicket() {
  const u = nu.value.trim(),
    s = ns.value.trim();
  if (!u || !s) {
    toast("User and subject are required");
    return;
  }
  const id = "CUS-2026-" + (100 + SUPT.length);
  SUPT.unshift({
    id,
    u,
    cat: nc.value,
    pri: np.value,
    st: "Open",
    sub: s,
    tl: ["Ticket created by " + who()],
  });
  log("created Ticket #" + id);
  document.querySelectorAll(".mod").forEach((x) => x.remove());
  S.tkt = 0;
  toast("Ticket " + id + " created");
  draw();
}

const MA = [
  ["Review", 0, "Under Review"],
  ["Warn", 0, "Action Taken"],
  ["Remove Content", 1, "Action Taken"],
  ["Restore", 0, "Resolved"],
  ["Temporary Suspend", 1, "Action Taken"],
  ["Permanent Ban", 1, "Action Taken"],
  ["No Action", 0, "Resolved"],
];
const rcard = (r) =>
  `<div class="card hv"><div class="row sp"><span class="badge">${r.id}</span><span class="badge">${r.st}</span></div><p style="margin:10px 0 4px"><b>${r.reason}</b> on ${r.target}</p><p class="mu">Reported by ${r.by} · ${r.d}</p><div class="row wrap" style="margin-top:10px">${MA.map((a, i) => `<button class="btn g s" onclick="mAct('${r.id}',${i})">${a[0]}</button>`).join("")}</div></div>`;
function mAct(id, i) {
  const r = REPORTS.find((x) => x.id == id),
    a = MA[i],
    go2 = () => {
      r.st = a[2];
      log(a[0].toLowerCase() + " on Report #" + id + " (" + r.target + ")");
      toast(a[0] + " recorded");
      draw();
    };
  a[1]
    ? ask(a[0] + " for " + r.target + "? This affects the user.", go2)
    : go2();
}
function userRows(q, f) {
  q = (q || "").toLowerCase();
  return (
    USERS.filter(
      (u) =>
        (!f || u.t == f || u.st == f) &&
        (u.n.toLowerCase().includes(q) || u.id.toLowerCase().includes(q)),
    )
      .map(
        (u) =>
          `<div class="card hv"><div class="row sp"><span class="badge">${u.id}</span><span class="badge">${u.st}</span></div><div class="row" style="margin:10px 0">${av(u.n)}<div><b>${u.n}</b><div class="mu">${u.t}</div></div></div><button class="btn g s" onclick="uAct('${u.id}')">${u.st == "Active" ? "Suspend" : "Reactivate"}</button></div>`,
      )
      .join("") || '<div class="card mu">No users match.</div>'
  );
}
function userFilter() {
  document.getElementById("ul").innerHTML = userRows(
    document.getElementById("uq").value,
    document.getElementById("uf").value,
  );
}
function uAct(id) {
  const u = USERS.find((x) => x.id == id),
    fin = () => {
      u.st = u.st == "Active" ? "Suspended" : "Active";
      log(
        (u.st == "Suspended" ? "suspended" : "reactivated") + " account " + id,
      );
      toast("Account " + u.st.toLowerCase());
      userFilter();
    };
  u.st == "Active"
    ? ask(
        "Suspend " + u.n + "? This is reversible — you can reactivate later.",
        fin,
      )
    : fin();
}
function saveSett() {
  const g = (i) => document.getElementById(i),
    n = g("s1").value.trim(),
    m = +g("s2").value,
    p = +g("s3").value,
    e = g("serr");
  if (!n) return (e.textContent = "Platform name is required.");
  if (!(m >= 1 && m <= 2000))
    return (e.textContent = "Max upload must be between 1 and 2000 MB.");
  if (!(p >= 1 && p <= 10))
    return (e.textContent = "Minimum portfolio projects must be 1–10.");
  Object.assign(SETT, {
    name: n,
    maxMb: m,
    minPort: p,
    signups: g("s4").checked,
  });
  e.textContent = "";
  log("updated platform settings");
  toast("Saved (this browser only)");
}

Object.keys(admin).forEach((k) => delete admin[k]);
Object.assign(admin, {
  dashboard: adminDash,
  restricted,
  users: () =>
    `<h2>Users</h2><div class="row wrap" style="margin-bottom:14px"><input id="uq" placeholder="Search name or ID" oninput="userFilter()" style="max-width:260px"><select id="uf" onchange="userFilter()" style="max-width:180px"><option value="">All</option><option>Creator</option><option>Editor</option><option>Active</option><option>Suspended</option></select></div><div class="grid" id="ul">${userRows("", "")}</div>`,
  verify: () => {
    const T = ["Under Review", "Additional Info", "Approved", "Rejected"];
    return `<h2>Editor Verification — final decisions</h2>${tabs(["Awaiting Decision", "Additional Info", "Approved", "Rejected"], "avt")}${grid(
      VCASES.filter((c) => c.status == T[S.avt || 0])
        .map((c) => vcard(c, "adm"))
        .join(""),
    )}`;
  },
  followup: () => {
    const T = [
      ["Pending"],
      ["Additional Info"],
      ["Under Review"],
      ["Approved", "Rejected"],
    ];
    return `<h2>Verification Follow-up</h2><p class="mu" style="margin-bottom:12px">Check completeness and follow up. Approve/reject belongs to Admins.</p>${tabs(["Completeness check", "Awaiting applicant", "With Admin", "Decided"], "fvt")}${grid(
      VCASES.filter((c) => T[S.fvt || 0].includes(c.status))
        .map((c) => vcard(c, "sup"))
        .join(""),
    )}`;
  },
  moderation: () =>
    `<h2>Moderation</h2><p class="mu" style="margin-bottom:12px">Reports are separate from support tickets.</p>${grid(REPORTS.map(rcard).join(""))}`,
  tickets: () => {
    const T = [
      "Open",
      "In Progress",
      "Awaiting User",
      "Escalated",
      "Resolved",
      "Closed",
    ];
    return `<div class="row sp wrap"><h2>Tickets</h2><button class="btn" onclick="newTicket()">+ New Ticket</button></div>${tabs(
      T.map((x) => x + " (" + SUPT.filter((t) => t.st == x).length + ")"),
      "tkt",
    )}${grid(
      SUPT.filter((t) => t.st == T[S.tkt || 0])
        .map(tcard)
        .join(""),
    )}`;
  },
  escalations: () =>
    `<h2>Escalations</h2><p class="mu" style="margin-bottom:12px">Tickets Customer Support could not resolve.</p>${grid(
      SUPT.filter((t) => t.st == "Escalated")
        .map(tcard)
        .join(""),
    )}`,
  settings: () =>
    `<h2>Platform Settings</h2><p class="mu" style="margin-bottom:12px">Stored in this browser only — prototype, changes nothing real.</p><div class="card" style="max-width:560px"><label>Platform name</label><input id="s1" value="${SETT.name}"><label>Max upload size (MB)</label><input id="s2" type=number value="${SETT.maxMb}"><label>Minimum portfolio projects for verification</label><input id="s3" type=number value="${SETT.minPort}"><div class="row sp" style="margin-top:12px"><span>Allow new sign-ups</span><input type=checkbox id="s4" ${SETT.signups ? "checked" : ""} style="width:auto"></div><p id="serr" role="alert" style="color:#EF4444;font-size:13px;margin-top:8px"></p><button class="btn" style="margin-top:8px" onclick="saveSett()">Save</button></div>`,
  notif: () =>
    `<h2>Notifications</h2><div class="card">${(ANOTIF[S.arole] || []).map((n) => `<p style="padding:10px 0;border-bottom:1px solid var(--bd)">${n}</p>`).join("")}</div>`,
  profile: () =>
    `<h2>Profile</h2><div class="card row">${av(who(), "#111")}<div><b>${who()}</b><div class="mu">${isAdm() ? "Administrator" : "Operational support role"}</div></div></div><p class="mu" style="margin-top:12px">Permissions here are a frontend demo only. Real authorization must be enforced on a server.</p>`,
});

function renderAdmin() {
  const nav = ROLES[S.arole].nav;
  return `<div class="app"><nav class="side"><div class="logo">Fra<b>me</b> <span class="mu" style="font-size:12px">Admin</span></div><div class="mu" style="font-size:11px;margin-bottom:4px">DEMO — switch identity (not real login)</div><select style="margin-bottom:12px" onchange="S.arole=this.value;adminOpen('dashboard')">${Object.keys(
    ROLES,
  )
    .map(
      (r) =>
        `<option value="${r}" ${S.arole == r ? "selected" : ""}>${ROLES[r].name}</option>`,
    )
    .join(
      "",
    )}</select>${nav.map((x) => `<div class="ni ${S.apg == x[0] ? "on" : ""}" onclick="adminOpen('${x[0]}')">${x[1]} ${x[2]}</div>`).join("")}<div class="ni" onclick="S.area='user';go('landing')">🚪 Logout</div></nav><div><div class="top"><b>${who()}</b><span style="cursor:pointer;margin-left:auto" onclick="adminOpen('notif')">🔔 ${(ANOTIF[S.arole] || []).length}</span><span style="cursor:pointer" onclick="theme();draw()">◐</span></div><div class="main">${(admin[S.apg] || admin.dashboard)()}</div></div></div><div class="mob">${nav
    .slice(0, 5)
    .map(
      (x) =>
        `<span class="${S.apg == x[0] ? "on" : ""}" onclick="adminOpen('${x[0]}')"><b>${x[1]}</b>${x[2].split(" ")[0]}</span>`,
    )
    .join("")}</div>`;
}
pages.adminlogin = () =>
  `<div style="min-height:100vh;display:grid;place-items:center;padding:16px"><div class="card" style="max-width:560px;width:100%"><div class="logo">Fra<b>me</b> <span class="mu" style="font-size:13px">Admin</span></div><h2>Choose an identity</h2><p class="mu" style="margin-bottom:14px">Prototype only — not real authentication.</p><div class="grid">${Object.keys(
    ROLES,
  )
    .map(
      (r) =>
        `<div class="card hv" style="cursor:pointer;padding:16px" onclick="S.arole='${r}';S.area='admin';S.apg='dashboard';go('admin')"><b>${ROLES[r].name}</b><p class="mu" style="margin-top:6px">${r == "support" ? "Tickets, verification follow-up" : "Users, verification, moderation, settings, escalations"}</p></div>`,
    )
    .join(
      "",
    )}</div><p class="mu" style="margin-top:14px"><a onclick="go('landing')">← Back to site</a></p></div></div>`;

draw();
