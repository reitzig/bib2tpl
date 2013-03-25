<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Example Publication List</title>
    <meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
  </head>

  <body>
    <h1>Publications</h1>

    @{group@
    <h3>@groupkey@ (@groupcount@)</h3>
    <ul>
      @{entry@
      <li>
        @author@, <strong>@title@</strong>, @year@
      </li>
      @}entry@
    </ul>
    @}group@
  </body>

</html>
