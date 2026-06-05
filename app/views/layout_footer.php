    </main><!-- end page-content -->
  </div><!-- end main-content -->
</div><!-- end app-layout -->

<div id="toast-container" class="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="/public/js/app.js"></script>
<script>
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const m = document.getElementById('mainContent');
  s.classList.toggle('collapsed');
  m.classList.toggle('sidebar-collapsed');
  // Logo swap
  const full  = s.querySelector('.sidebar-logo-full');
  const short = s.querySelector('.sidebar-logo-short');
  const collapsed = s.classList.contains('collapsed');
  if (full)  full.style.display  = collapsed ? 'none'         : '';
  if (short) short.style.display = collapsed ? 'inline-block' : 'none';
  // Toggle button icon direction
  const btn = s.querySelector('.sidebar-toggle');
  if (btn) btn.style.transform = collapsed ? 'rotate(180deg)' : '';
}
function switchOrg(val) { if (val) window.location.href = '/org/switch?id=' + val; }
</script>
</body>
</html>
