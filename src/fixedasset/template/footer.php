</div> <!-- end container -->
<footer class="text-center mt-4 mb-3 text-muted">
     Nara Thai Cuisine - Fixed asset management system &copy; <?=date('Y')?>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Scroll Toggle Button -->
<button id="scrollBtn" onclick="handleScroll()" class="btn btn-primary position-fixed bottom-0 end-0 m-3" title="เลื่อน">
  ⬇️
</button>

<script>
const scrollBtn = document.getElementById("scrollBtn");

function handleScroll() {
  if (isAtBottom()) {
    window.scrollTo({ top: 0, behavior: "smooth" });
  } else {
    window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
  }
}

function isAtBottom() {
  return (window.innerHeight + window.scrollY) >= document.body.scrollHeight - 5;
}

function updateArrow() {
  if (isAtBottom()) {
    scrollBtn.innerHTML = "⬆️";
    scrollBtn.title = "เลื่อนขึ้น";
  } else {
    scrollBtn.innerHTML = "⬇️";
    scrollBtn.title = "เลื่อนลง";
  }
}
window.addEventListener("scroll", updateArrow);
window.addEventListener("load", updateArrow);
</script>

</body>
</html>
