(function () {
  document.addEventListener("DOMContentLoaded", function () {
    var dataObj = window.pdContribData || {};
    var counts = dataObj.counts || [];
    var months = dataObj.months || [];

    var canvas = document.getElementById("pd-contrib-chart");
    if (!canvas) {
      return;
    }
    var ctx = canvas.getContext("2d");

    new Chart(ctx, {
      type: "bar",
      data: {
        labels: months,
        datasets: [
          {
            label: "Contributions",
            data: counts,
            borderWidth: 1,
          },
        ],
      },
      options: {
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { precision: 0 } },
        },
        plugins: {
          legend: { display: false },
        },
      },
    });
  });
})();
