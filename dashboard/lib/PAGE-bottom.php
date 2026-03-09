<?php if ($_ISADMIN) { ?>
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <strong>Copyright &copy; <?=date('Y')?> 
      <a href="http://paper-trade.org/">PaperTrade Inc</a>
    </strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>
<?php } ?>
<!-- ./wrapper -->

<!-- ✅ Chart.js (line chart only) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- ✅ ApexCharts (candlestick) -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- jQuery -->
<script src="<?=URL_HOST?>plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI -->
<script src="<?=URL_HOST?>plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Bootstrap -->
<script src="<?=URL_HOST?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Sparkline -->
<script src="<?=URL_HOST?>plugins/sparklines/sparkline.js"></script>
<!-- jQuery Knob -->
<script src="<?=URL_HOST?>plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="<?=URL_HOST?>plugins/moment/moment.min.js"></script>
<script src="<?=URL_HOST?>plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="<?=URL_HOST?>plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="<?=URL_HOST?>plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="<?=URL_HOST?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="<?=URL_HOST?>dist/js/adminlte.js"></script>
<!-- FullCalendar -->
<script src="<?=URL_HOST?>plugins/fullcalendar/main.js"></script>

<!-- Page specific script (FullCalendar + Drag & Drop) -->
<script>
  $(function () {

    function ini_events(ele) {
      ele.each(function () {
        var eventObject = {
          title: $.trim($(this).text())
        }
        $(this).data('eventObject', eventObject)
        $(this).draggable({
          zIndex        : 1070,
          revert        : true,
          revertDuration: 0
        })
      })
    }

    ini_events($('#external-events div.external-event'))

    var date = new Date()
    var d    = date.getDate(),
        m    = date.getMonth(),
        y    = date.getFullYear()

    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('calendar');

    new Draggable(containerEl, {
      itemSelector: '.external-event',
      eventData: function(eventEl) {
        return {
          title: eventEl.innerText,
          backgroundColor: window.getComputedStyle(eventEl).getPropertyValue('background-color'),
          borderColor: window.getComputedStyle(eventEl).getPropertyValue('background-color'),
          textColor: window.getComputedStyle(eventEl).getPropertyValue('color'),
        };
      }
    });

    var calendar = new Calendar(calendarEl, {
      headerToolbar: {
        left  : 'prev,next today',
        center: 'title',
        right : 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      themeSystem: 'bootstrap',
      editable  : true,
      droppable : true,
      events: [
        {
          title: 'All Day Event',
          start: new Date(y, m, 1),
          backgroundColor: '#f56954',
          borderColor: '#f56954',
          allDay: true
        }
      ],
      drop: function(info) {
        if (checkbox.checked) {
          info.draggedEl.parentNode.removeChild(info.draggedEl);
        }
      }
    });

    calendar.render();

    var currColor = '#3c8dbc'
    $('#color-chooser > li > a').click(function (e) {
      e.preventDefault()
      currColor = $(this).css('color')
      $('#add-new-event').css({'background-color': currColor, 'border-color': currColor})
    })

    $('#add-new-event').click(function (e) {
      e.preventDefault()
      var val = $('#new-event').val()
      if (val.length == 0) return

      var event = $('<div />')
      event.css({
        'background-color': currColor,
        'border-color'    : currColor,
        'color'           : '#fff'
      }).addClass('external-event')
      event.text(val)
      $('#external-events').prepend(event)

      ini_events(event)
      $('#new-event').val('')
    })
  })
</script>




</body>
</html>
