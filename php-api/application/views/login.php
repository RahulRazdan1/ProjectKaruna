<div class="login-box">
   <div class="login-logo">
      <a><b>Karuna</b></a>
   </div>
   <!-- /.login-logo -->
   <div class="login-box-body">
      <p class="login-box-msg">Sign in to start your session</p>
      <?php echo validation_errors('<div class="alert alert-danger">','</div>'); ?>
      <?php 
         if(isset($_SESSION['error'])){
         ?>
      <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
      <?php
         }
         ?>
      <form action="<?php echo site_url('auth/index'); ?>" method="post">
         <div class="form-group has-feedback">
            <input type="email" name="email" class="form-control" placeholder="Email">
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
         </div>
         <div class="form-group has-feedback">
            <input type="password" name="password" class="form-control" placeholder="Password">
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
         </div>
         <?php
            $message = $this->session->flashdata('message');
            if (isset($message)) {
            echo '<div class="alert alert-info">' . $message . '</div>';
            $this->session->unset_userdata('message');
            }
            
            ?>
         <div class="row">
            <div class="col-xs-12">
               <button type="submit" name='login' class="btn btn-primary btn-block btn-flat">Sign In</button>
            </div>
            <div class="login-meta-data text-center">
               <a class="stretched-link forgot-password d-block mt-3 mb-1" href="#">Forgot Password?</a>
               <p class="mb-0">Didn't have an account? <a class="stretched-link" href="<?php echo base_url('register/index'); ?>">Register Now</a></p>
            </div>
         </div>
      </form>
   </div>
</div>