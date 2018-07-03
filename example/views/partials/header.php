<div>
    <h1> This is header <?php $template->section_declare('header_title'); ?> </h1>
    | <?php $template->load_view('partials/header_child1'); ?> |
    | <?php $template->load_view('partials/header_child2'); ?> |
</div>
<hr/>