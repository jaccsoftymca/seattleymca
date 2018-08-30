<style>
  table {
    color: #333;
    font-family: Helvetica, Arial, sans-serif;
    width: 640px;
    border-collapse:
      collapse; border-spacing: 0;
  }

  td, th {
    border: 1px solid transparent; /* No more visible border */
    height: 30px;
    transition: all 0.3s;  /* Simple transition for hover effect */
  }

  th {
    background: #DFDFDF;  /* Darken header a bit */
    font-weight: bold;
  }

  td {
    background: #FAFAFA;
    text-align: center;
  }

  /* Cells in even rows (2,4,6...) are one color */
  tr:nth-child(even) td { background: #F1F1F1; }

  /* Cells in odd rows (1,3,5...) are another (excludes header cells)  */
  tr:nth-child(odd) td { background: #FEFEFE; }
</style>
<?php

include 'salesforce_cli.php';
