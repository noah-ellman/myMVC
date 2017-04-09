# myMVC
My own little MVC framework for smaller projects

This little framework is a super like a super slim version of Laravel.  
It implements many of the basic MVC featuers of Laravel but that's all really. 
It does basic routing in a simple easy way to all your controllers which are based on file names. 

The `Response` and `Request` classes are all extended from Symphony classes and are PSR compatible. 

There is no view templating engine.  You can use regular php file in the view folder. Views are still sandboxed however and data must be added yourself like other frameworks, although many variables are automatically put in scope. 
