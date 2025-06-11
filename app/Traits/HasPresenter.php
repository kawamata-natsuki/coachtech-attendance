<?php

namespace App\Traits;

use App\Presenters\AttendancePresenter;

trait HasPresenter
{
  public function present()
  {
    return new AttendancePresenter($this);
  }
}
