window.addEventListener("load", function() {
  const r = document.querySelectorAll('a[href*="#brochure"]'), t = document.querySelector("#modal-form"), c = document.querySelector("#modal-form-close");
  let l;
  t && getComputedStyle(t).display === "block" && (document.body.style.overflow = "hidden"), r && t && c && (r.forEach(function(o) {
    o.addEventListener("click", function(n) {
      n.preventDefault();
      const e = o.getAttribute("href");
      if (e.includes("?")) {
        const i = e.split("?");
        if (i.length > 1) {
          const s = i[1].split("=");
          s[0] === "file" && (l = s[1]);
        }
      }
      t.style.display = "block", document.body.style.overflow = "hidden";
    });
  }), c.addEventListener("click", function() {
    t.style.display = "none", document.body.style.overflow = "auto", document.body.style.overflowX = "hidden";
  }), window.addEventListener("click", function(o) {
    o.target === t && (t.style.display = "none", document.body.style.overflow = "auto", document.body.style.overflowX = "hidden");
  }));
  const d = document.querySelector("#modal-form form");
  d && d.addEventListener("submit", function(o) {
    o.preventDefault();
    const n = new FormData(d);
    l && (n.delete("file"), n.append("file", l));
    const e = new XMLHttpRequest();
    e.open("POST", d.action, !0), e.onreadystatechange = function() {
      e.readyState === 4 && e.status === 200 && (t.querySelector(".message").innerHTML = e.responseText);
    }, e.send(n);
  });
});
