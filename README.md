# Yii2 AWS S3Client

[![@raditzfarhan on Twitter](https://img.shields.io/badge/twitter-%40raditzfarhan-blue.svg?style=flat)](https://twitter.com/raditzfarhan)

The **Yii2 AWS S3Client** is a wrapper for AWS S#Client SDK for PHP. 

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/). 


### Pre-requisites

> You must have [composer](http://getcomposer.org/download/) installed on your machine.

### Install

Either run

```
$ composer require farhan928/yii2-aws-s3-client "*"
```

or add

```
"farhan928/yii2-aws-s3-client": "*"
```

to the ```require``` section of your `composer.json` file.

## Usage

Add this code below to your main config and your are ready to go.
```php
	// add this in your component section
	's3Client' => [           
            'class' => 'farhan928\AwsS3\S3Client',
            'key' => 'YOUR_AWS_KEY',
            'secret' => 'YOUR_AWS_SECRET',            
            'bucket' => 'YOUR_BUKCET',            
    ],
```

## Examples

### Get object URL
```php
    Yii::$app->s3Client->url('file.png');
```

### Generate and get temporary URL
```php
    Yii::$app->s3Client->temporaryUrl('file.png', 5); //second argument is the duration in minute
```

### Upload raw file contents on bucket
```php
    Yii::$app->s3Client->put('file.png', file_get_contents('https://i.imgur.com/hAjCMan.jpg'))
```

### Copy object to new location
```php
    Yii::$app->s3Client->copy('old.png', 'file/new.png')
```

### Move object to new location
```php
    Yii::$app->s3Client->move('old.png', 'file/new.png')
```

## License

**yii2-aws-s3-client** is released under the BSD-3-Clause License

### Related Links

* [AWS SDK for PHP - Version 3][aws-sdk-php-github]

[aws-sdk-php-github]: https://github.com/aws/aws-sdk-php

