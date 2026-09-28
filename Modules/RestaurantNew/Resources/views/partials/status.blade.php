@php($value=$status??'unknown')<span class="rest-status rest-status-{{ str_replace('_','-',$value) }}">{{ ucwords(str_replace('_',' ',$value)) }}</span>
