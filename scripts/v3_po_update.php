<?php

declare(strict_types=1);

const START_TOKEN = "/^msgid \"\"\n/";
const END_TOKEN = "/^msgstr \"/";

function correctBuffer(string $buffer): string
{
    $lines = explode("\n", $buffer);

    $rebuilt = '';
    foreach ($lines as $line) {
        $line = trim(trim($line), '"');
        $rebuilt .= $line;
    }

    $rebuilt = preg_replace('/\\\\n/', ' ', $rebuilt);

    return sprintf("msgid \"%s\"\n", trim(preg_replace('/\s{2,}/', ' ', $rebuilt)));
}

try {
    $file_in = fopen('messages.po', 'r');
    $file_out = fopen('messages_fixed.po', 'w');

    $in_block = false;
    $correction_buffer = '';

    while (($buffer = fgets($file_in)) !== false) {
        if (preg_match(START_TOKEN, $buffer)) {
            $in_block = true;
            $correction_buffer = '';
            continue;
        }

        if (!$in_block) {
            fputs($file_out, $buffer);
        } elseif (preg_match(END_TOKEN, $buffer)) {
            fputs($file_out, correctBuffer($correction_buffer));
            fputs($file_out, $buffer);

            $in_block = false;
            $correction_buffer = '';
        } else {
            $correction_buffer .= $buffer;
        }
    }
} catch (Exception $ex) {
    print $ex;
} finally {
    fclose($file_in);
    fclose($file_out);
}
