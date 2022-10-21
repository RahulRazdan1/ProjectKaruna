<div class="login-box">
   <div class="login-logo">
      <a><b>Register Here</b></a>
   </div>
   <div class="login-box-body">
      <form action="<?php echo site_url('register/index'); ?>" method="post">
         <div class="form-group has-feedback">
            <input type="text" name="firstName" class="form-control" placeholder="First Name">
			<span class="text-danger"><?php echo form_error('firstName');?></span>
         </div>
         <div class="form-group has-feedback">
            <input type="text" name="lastName" class="form-control" placeholder="Last Name">
			<span class="text-danger"><?php echo form_error('lastName');?></span>
         </div>
         <div class="form-group has-feedback">
            <input type="text" name="mobile" class="form-control" placeholder="Mobile">
		    <span class="text-danger"><?php echo form_error('mobile');?></span>
         </div>
         <div class="form-group has-feedback">
            <input type="email" name="email" class="form-control" placeholder="Email">
		    <span class="text-danger"><?php echo form_error('email');?></span>
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
         </div>
         <div class="form-group has-feedback">
            <input type="password" name="password" class="form-control" placeholder="Password">
			<span class="text-danger"><?php echo form_error('password');?></span>
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
         </div>
         <div class="row">
            <div class="col-xs-12">
               <button type="submit" name="register" class="btn btn-primary btn-block btn-flat">Register</button>
            </div>
            <div class="login-meta-data text-center">
               <p class="mb-0">Already have an account? <a class="stretched-link" href="<?php echo base_url('auth/index'); ?>">Login</a></p>
            </div>
         </div>
      </form>
   </div>
</div>