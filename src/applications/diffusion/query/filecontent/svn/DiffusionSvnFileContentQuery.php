<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class DiffusionSvnFileContentQuery extends DiffusionFileContentQuery {

  protected function executeQuery() {
    $drequest = $this->getRequest();

    $repository = $drequest->getRepository();
    $path = $drequest->getPath();
    $commit = $drequest->getCommit();

    $remote_uri = $repository->getDetail('remote-uri');

    try {
      list($corpus) = execx(
        'svn --non-interactive cat %s%s@%s',
        $remote_uri,
        $path,
        $commit);
    } catch (CommandException $ex) {
      $stderr = $ex->getStdErr();
      if (preg_match('/path not found$/', trim($stderr))) {
        // TODO: Improve user experience for this. One way to end up here
        // is to have the parser behind and look at a file which was recently
        // nuked; Diffusion will think it still exists and try to grab content
        // at HEAD.
        throw new Exception(
          "Failed to retrieve file content from Subversion. The file may ".
          "have been recently deleted, or the Diffusion cache may be out of ".
          "date.");
      } else {
        throw $ex;
      }
    }

    $file_content = new DiffusionFileContent();
    $file_content->setCorpus($corpus);

    return $file_content;
  }

}
