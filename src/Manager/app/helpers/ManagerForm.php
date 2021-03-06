<?php
namespace Helper\Manager;

class ManagerForm
{
    public function render(Array $args, Array $options)
    {
        $idSpare = $options['spare'];
        $metadata = $options['metadata'];

        return '
            <form class="manager" data-xhr="true" method="post" data-idSpare="'.$idSpare.'" data-titleField="'.$metadata['titleField'].'" data-singular="'.$metadata['singular'].'" action="/Manager/api/upsert/'.$metadata['link'].'" data-manager="'.$metadata['link'].'">
                <input type="submit" style="position: absolute; visibility: hidden" />';
    }
}
