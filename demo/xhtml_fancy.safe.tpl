<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Example Publication List</title>
    <meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />

    <link rel="stylesheet" href="res/style.css" type="text/css" />
    <script type="text/javascript" src="res/jquery-1.4.3.min.js"></script>
    <script type="text/javascript" src="res/script.js"></script>

    <script type="text/javascript">
      $(document).ready(function() {
        init();
      });
    </script>
  </head>

  <body>
    <h2 class="publist_hl">Publications</h2>

    @{group@
      <h3 class="publist_grouphl">
        <span id="grouplink_@groupid@" class="publist_jslink">@groupkey@</span>
        <span class="publist_groupcount">(@groupcount@)</span>
      </h3>
      <div id="group_@groupid@" class="publist_groupdiv">
        <ul>
        @{entry@
          <li id="entry_@entrykey@">
            <span class="publist_author">@author@</span><br />
            <span class="publist_title">@title@</span><br />
            @?journal@<span class="publist_rest">@journal@@?volume@ @volume@@?number@ (@number@)@;number@@;volume@</span>, @;journal@
            @?publisher@<span class="publist_rest">@publisher@</span>, @;publisher@
            @?address@<span class="publist_rest">@address@</span>, @;address@
            <span class="publist_date">@?year@@?month@@month@ @;month@@year@@;year@</span>

            <div id="links_@entrykey@" class="publist_links">
              @?abstract@<span id="abstractlink_@entrykey@" class="publist_jslink" title="Show abstract">abs</span>@;abstract@
              @?pdf@<a href="@pdf@" title="Download PDF">pdf</a>@;pdf@
              @?doi@<a href="http://dx.doi.org/@doi@" target="_blank" title="">doi</a>@;doi@
              @?url@<a href="@url@" target="_blank" title="Visit website">web</a>@;url@
              @?bibtex@<span id="bibtexlink_@entrykey@" class="publist_jslink" title="Show BibTeX">bib</span>@;bibtex@
            </div>

            @?abstract@
            <div id="abstract_@entrykey@" class="publist_popbox">
              <pre>@abstract@</pre>
              <span id="abstractlinkc_@entrykey@" class="publist_jslink">close</span>
            </div>
            @;abstract@

            @?bibtex@
            <div id="bibtex_@entrykey@" class="publist_popbox">
              <pre>@bibtex@</pre>
              <span id="bibtexlinkc_@entrykey@" class="publist_jslink">close</span>
            </div>
            @;bibtex@
          </li>
        @}entry@
        </ul>
      </div>
    @}group@
  </body>

</html>
