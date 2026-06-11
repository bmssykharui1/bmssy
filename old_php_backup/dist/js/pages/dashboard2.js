$(function () {
  'use strict'

  var salesChartCanvas = $('#salesChart').get(0).getContext('2d');

  // Fetch data from PHP
  $.ajax({
      url: 'fetch_chart_data.php',
      method: 'GET',
      dataType: 'json',
      success: function(response) {
          if (response.error) {
              console.error("Error: " + response.error);
              return;
          }

          var labels = [];
          var data = [];

          response.forEach(item => {
              labels.push(item.ssin_prefix); // '142' or '242'
              data.push(item.total); // Count of SSINs
          });

          var salesChartData = {
              labels: labels,
              datasets: [{
                  label: 'Active SSINs',
                  backgroundColor: ['rgba(60,141,188,0.9)', 'rgba(210, 214, 222, 1)'],
                  borderColor: ['rgba(60,141,188,0.8)', 'rgba(210, 214, 222, 1)'],
                  data: data
              }]
          };

          var salesChartOptions = {
              maintainAspectRatio: false,
              responsive: true,
              legend: { display: true },
              scales: {
                  x: { grid: { display: false } },
                  y: { beginAtZero: true }
              }
          };

          new Chart(salesChartCanvas, {
              type: 'bar',
              data: salesChartData,
              options: salesChartOptions
          });
      },
      error: function(xhr, status, error) {
          console.error("AJAX Error: " + error);
      }
  });

});
