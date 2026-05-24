<?php $__env->startSection('content'); ?>
  <h1>About Wind For Life</h1>
  <p>
    Wind For Life is a non-profit collecting real-time wind speed readings
    (in knots) from a network of anemometers deployed around the world.
  </p>
  <p>
    This application provides the backend API used to manage those anemometers
    and the readings they submit, as well as aggregated statistics (daily and
    weekly mean wind speeds) computed from those readings.
  </p>
  <p>
    All API endpoints require authentication. Obtain a token via
    <code>POST /api/auth-token</code> and pass it as a bearer token on
    subsequent requests.
  </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/pages/about.blade.php ENDPATH**/ ?>