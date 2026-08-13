@extends('errors.layout')

@section('title', 'Too many requests')
@section('code', '429')
@section('heading', 'Slow down a little')
@section('message', 'You\'ve made too many requests in a short time. Please wait a moment and try again.')
