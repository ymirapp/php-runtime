<p align="center">
    <a href="https://ymirapp.com" target="_blank" align="center">
        <img src="https://cdn-std.droplr.net/files/acc_680806/69fc3k" width="280">
    </a>
</p>

# Ymir PHP Runtime

[![Actions Status](https://github.com/ymirapp/php-runtime/workflows/Continuous%20Integration/badge.svg)](https://github.com/ymirapp/php-runtime/actions)

[Ymir][1] PHP runtime for [AWS Lambda][2].

## Acknowledgements

This PHP runtime wouldn't exist without the tireless work of [Matthieu Napoli][3] and the rest of the [bref][4] contributors. 
Most of the code to compile PHP started from their implementation. The initial inspiration for the PHP code comes from an 
[interview][5] between [Adam Wathan][6] and [Taylor Otwell][7] about [Laravel Valet][8].

## How to use the runtime

The runtime layers are publicly available. You can just add one to your Lambda function. You'll find all the current layer ARN in the `layers.json` or `layers.php` files. 

## Contributing

Install dependencies using composer:

```console
$ composer install
```

Run tests using phpunit:

```console
$ vendor/bin/phpunit
```

## Links

 * [Documentation][9]

[1]: https://ymirapp.com
[2]: https://aws.amazon.com/lambda/
[3]: https://github.com/mnapoli
[4]: https://github.com/brefphp/bref
[5]: https://fullstackradio.com/120
[6]: https://github.com/adamwathan
[7]: https://github.com/taylorotwell
[8]: https://github.com/laravel/valet
[9]: https://docs.ymirapp.com
